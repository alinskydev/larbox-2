<?php

namespace App\Console\Commands\Larbox;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\StreamOutput;

class ExportPostmanCollectionCommand extends Command
{
    protected $signature = 'larbox:export_postman_collection';

    protected $description = 'Export postman collection';

    private int $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public function handle(): void
    {
        $path = base_path('storage/larbox');

        if (!is_dir($path)) {
            $this->error("'$path' doesn't exists");
            exit;
        }

        $collectionName = $this->ask('Enter collection name', config('app.name'));
        $collectionName = Str::slug($collectionName) . '_' . date('Y_m_d__H_i_s') . '_collection.json';

        $inputFileName = "$path/_postman.json";
        $outputFileName = "$path/$collectionName";

        File::delete($inputFileName);

        $stream = fopen('php://output', 'w');
        $exitCode = Artisan::call('larbox:test', [], new StreamOutput($stream));

        if (!in_array($exitCode, [0, 1])) return;

        // Preparing data

        $inputData = file_get_contents($inputFileName);
        $inputData = json_decode($inputData, true);
        $inputData = Arr::undot($inputData);

        $outputData = [
            'info' => [
                'name' => $collectionName,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                [
                    'key' => 'host',
                    'value' => '<host>',
                    'type' => 'string',
                ],
            ],
            'item' => $this->buildItems($inputData),
        ];

        // Saving data

        $outputData = json_encode($outputData, $this->jsonOptions);
        file_put_contents($outputFileName, $outputData);

        $this->info('Successfully done');
    }

    private function buildItems(array $items): array
    {
        $data = [];

        foreach ($items as $name => $item) {
            if (isset($item['is_request'])) {
                $request = $item['request'];
                $response = $item['response'];

                $formdata = [
                    $this->buildKeyValue($request['body'], ['type' => 'text']),
                    $this->buildKeyValue($request['files'], ['type' => 'file']),
                ];

                $formdata = array_merge(...$formdata);
                $formdata = Arr::sort($formdata, 'key');
                $formdata = array_values($formdata);

                $requestBody = [
                    'mode' => 'formdata',
                    'formdata' => $formdata,
                ];

                $data[] = [
                    '_postman_previewlanguage' => 'json',
                    'name' => ucfirst(str_replace('_', ' ', $name)),
                    'status' => $response['status']['text'],
                    'code' => $response['status']['code'],
                    'header' => $this->buildKeyValue($response['headers']),
                    'body' => $response['body'] ? json_encode($response['body'], $this->jsonOptions) : null,
                    'originalRequest' => [
                        'method' => $request['method'],
                        'header' => $this->buildKeyValue($request['headers']),
                        'body' => $requestBody,
                        'url' => $this->buildUrl($request),
                    ],
                ];
            } elseif (isset(current($item)['is_request'])) {
                $request = current($item)['request'];
                $comment = current($item)['comment'];

                $data[] = [
                    'name' => implode(' ', Str::ucsplit($name)),
                    'request' => [
                        'method' => $request['method'],
                        'header' => $this->buildKeyValue($request['headers']),
                        'body' => [
                            'mode' => 'formdata',
                            'formdata' => [],
                        ],
                        'url' => $this->buildUrl($request),
                        'description' => $comment,
                    ],
                    'response' => $this->buildItems($item),
                ];
            } else {
                $data[] = [
                    'name' => implode(' ', Str::ucsplit($name)),
                    'item' => $this->buildItems($item),
                ];
            }
        }

        return $data;
    }

    private function buildKeyValue(array $array, array $additionalElements = []): array
    {
        return Arr::map($array, fn($value, $key) => [
            ...$additionalElements,
            'key' => $key,
            'value' => $value,
        ]);
    }

    private function buildUrl(array $request): array
    {
        return [
            'host' => ['{{host}}'],
            'raw' => '{{host}}/' . $request['url']['raw'],
            'path' => explode('/', $request['url']['path']),
            'query' => $this->buildKeyValue($request['url']['query']),
        ];
    }
}
