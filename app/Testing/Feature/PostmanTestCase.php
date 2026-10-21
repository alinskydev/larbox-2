<?php

namespace App\Testing\Feature;

use App\Testing\CreatesApplicationTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostmanTestCase extends BaseTestCase
{
    use CreatesApplicationTrait;
    use DatabaseTransactions {
        beginDatabaseTransaction as baseBeginDatabaseTransaction;
    }

    protected bool $shouldShowDetailedError;
    protected bool $shouldExportToPostman = true;

    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'Accept-Language' => 'en',
    ];

    public array $connectionsToTransact = [
        'main',
        'history',
    ];

    public int $userId;

    public string $requestUrl;
    private string $requestMethod;

    public array $requestHeaders = [];
    private array $allRequestHeaders = [];

    private array $requestQuery = [];
    private string $requestQueryAsString;

    private array $requestBody = [];
    private array $requestFiles = [];

    public TestResponse $response;

    private static array $pdos = [];
    private static string $postmanFilePath;
    private static array $postmanItems;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shouldShowDetailedError = config('larbox.testing.show_detailed_error');

        static::$postmanFilePath = storage_path('larbox/_postman.json');

        if (isset($this->userId)) {
            $this->authAsUser($this->userId);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(static::$postmanItems)) {
            $items = json_encode(static::$postmanItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            file_put_contents(static::$postmanFilePath, $items);
        }

        parent::tearDownAfterClass();
    }

    public function beginDatabaseTransaction(): void
    {
        $connectionNames = [...$this->connectionsToTransact, 'telescope'];

        foreach ($connectionNames as $name) {
            $connection = DB::connection($name);

            if (isset(static::$pdos[$name])) {
                $connection->setPdo(static::$pdos[$name]);
            } else {
                static::$pdos[$name] = $connection->getPdo();
            }
        }

        $this->baseBeginDatabaseTransaction();
    }

    protected function authAsUser(int $userId): void
    {
        $user = setUser($userId);
        $this->actingAs($user, 'sanctum');
    }

    public function sendRequest(
        string $method = 'GET',
        string $path = '',
        array $query = [],
        array $body = [],
        int $assertStatus = 200,
        int $throttleCount = 0,
    ): void {
        $this->requestMethod = $method;
        $this->requestUrl .= $path ? "/$path" : '';
        $this->requestQuery = $query;
        $this->requestBody = $body;

        for ($i = 0; $i <= $throttleCount; $i++) {
            $this->response = $this->sendRequestAndPrepareData();

            try {
                $assertStatus = $throttleCount > 0 && $i === $throttleCount ? 429 : $assertStatus;
                $this->response->assertStatus($assertStatus);
            } catch (\Throwable $e) {
                $response = $this->response->baseResponse;
                $exception = $response->exception;

                if ($this->shouldShowDetailedError && $exception) {
                    $trace = array_slice($exception->getTrace(), 0, 6);
                    $trace = data_forget($trace, '*.args');

                    $error = [
                        'target' => $this->provides()[0]->getTarget(),
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $trace,
                    ];

                    echo '<pre>';
                    print_r($error);
                    echo '</pre>';
                    exit;
                }

                $content = json_encode(json_decode($response->getContent()), JSON_UNESCAPED_UNICODE);
                $response->setContent($content);

                throw new \Exception($response);
            }
        }
    }

    private function sendRequestAndPrepareData(): TestResponse
    {
        $this->allRequestHeaders = array_merge($this->defaultHeaders, $this->requestHeaders);
        $this->allRequestHeaders['Authorization'] = isset($this->userId) ? '{{token}}' : '';

        if (in_array($this->requestMethod, ['PUT', 'PATCH'])) {
            $this->requestQuery['_method'] = $this->requestMethod;
            $this->requestMethod = 'POST';
        }

        $this->requestQueryAsString = http_build_query($this->requestQuery);

        return $this->call(
            method: $this->requestMethod,
            uri: $this->requestQueryAsString ? "$this->requestUrl?$this->requestQueryAsString" : $this->requestUrl,
            parameters: $this->requestBody,
            server: $this->transformHeadersToServerVars($this->allRequestHeaders),
        );
    }

    public function callBeforeApplicationDestroyedCallbacks(): void
    {
        if (!isset($this->response) || !$this->shouldExportToPostman) {
            parent::callBeforeApplicationDestroyedCallbacks();
            return;
        }

        $this->prepareRequestQuery();
        $this->prepareRequestBody();

        [$targetPath, $comment] = $this->getTargetAndParseComment();
        $this->addPostmanItem($targetPath, $comment);

        parent::callBeforeApplicationDestroyedCallbacks();
    }

    private function prepareRequestQuery(): void
    {
        $this->requestQuery = $this->requestQuery ? Str::of(http_build_query($this->requestQuery))
            ->explode('&')
            ->map(fn($pair) => [
                urldecode(Str::before($pair, '=')) => urldecode(Str::after($pair, '=')),
            ])
            ->collapse()
            ->filter()
            ->toArray() : [];
    }

    private function prepareRequestBody(): void
    {
        array_walk_recursive($this->requestBody, function (&$value, $key) {
            $value = $value instanceof File ? "_FILE_$value->name" : $value;
        });

        $this->requestBody = $this->requestBody ? Str::of(http_build_query($this->requestBody))
            ->explode('&')
            ->map(fn($pair) => [
                urldecode(Str::before($pair, '=')) => urldecode(Str::after($pair, '=')),
            ])
            ->collapse()
            ->filter(function ($value, $key) {
                if (!str_starts_with($value, '_FILE_')) return true;

                $this->requestFiles[$key] = Str::replaceFirst('_FILE_', '', $value);
                return false;
            })
            ->toArray() : [];
    }

    private function getTargetAndParseComment(): array
    {
        $target = $this->provides()[0]->getTarget();

        $targetPath = preg_replace('/Http\\\|Tests\\\|Test|test_/', '', $target);
        $targetPath = str_replace(['\\', '::'], '.', $targetPath);
        $targetPath = str_replace('___', ' | ', $targetPath);

        $targetParts = explode('::', $target);
        $targetClass = new \ReflectionClass($targetParts[0]);

        $comment = explode("\n", $targetClass->getDocComment());
        $comment = array_map(fn($value) => ltrim($value, '/* '), $comment);
        $comment = implode("\n", $comment);
        $comment = trim($comment);

        return [$targetPath, $comment];
    }

    private function addPostmanItem(string $targetPath, string $comment): void
    {
        if (!isset(static::$postmanItems)) {
            $items = is_file(static::$postmanFilePath) ? file_get_contents(static::$postmanFilePath) : '[]';
            static::$postmanItems = json_decode($items, true);
        }

        /** @var JsonResponse|StreamedResponse */
        $baseResponse = $this->response->baseResponse;

        if ($baseResponse instanceof StreamedResponse) {
            $responseData = [
                'headers' => $baseResponse->headers->allPreserveCase(),
                'body' => 'Binary data',
                'status' => [
                    'code' => 200,
                    'text' => 'OK',
                ],
            ];
        } else {
            $responseData = [
                'headers' => $baseResponse->headers->allPreserveCase(),
                'body' => json_decode($baseResponse->content(), true),
                'status' => [
                    'code' => $baseResponse->status(),
                    'text' => $baseResponse->statusText(),
                ],
            ];
        }

        static::$postmanItems[$targetPath] = [
            'is_request' => true,
            'request' => [
                'headers' => $this->allRequestHeaders,
                'body' => $this->requestBody,
                'files' => $this->requestFiles,
                'method' => $this->requestMethod,
                'url' => [
                    'path' => $this->requestUrl,
                    'query' => $this->requestQuery,
                    'raw' => $this->requestQueryAsString ? "$this->requestUrl?$this->requestQueryAsString" : $this->requestUrl,
                ],
            ],
            'response' => $responseData,
            'comment' => $comment,
        ];
    }
}
