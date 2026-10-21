<?php

namespace App\Integration;

use App\Integration\DTOs\IntegrationRequestOptionsDTO;
use App\Integration\DTOs\IntegrationServiceConfigDTO;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Company\Models\Company;

abstract class IntegrationService
{
    public ?Company $company;
    public IntegrationServiceConfigDTO $config;
    protected string $language;

    public IntegrationRequestOptionsDTO $requestOptions;
    public string $requestPath;
    public array $requestBody;

    public array $additionalData = [];
    public array $forcedLoginStatuses = [401, 403, 500];

    public function __construct(?Company $company = null)
    {
        $this->company = $company ?? user()->company ?? null;
        $this->config = $this->getConfig();
        $this->language = app()->getLocale();

        $this->ensurePermission();
    }

    abstract protected function getConfig(): IntegrationServiceConfigDTO;
    abstract protected function headers(): array;
    abstract protected function processRequest(PendingRequest $request): Response;
    abstract protected function ensureSuccessful(Response $response): Response;

    protected function ensurePermission(): void {}
    protected function login(): void {}

    protected function executeWithRelogin(\Closure $callback, bool $shouldRetryLogin = true): Response
    {
        $pendingRequest = Http::baseUrl($this->config->url)
            ->withHeaders($this->headers());

        /** @var Response */
        $response = $callback($pendingRequest);

        if (!in_array($response->status(), $this->forcedLoginStatuses)) {
            return $response;
        }

        if ($shouldRetryLogin) {
            $this->login();
            return $this->executeWithRelogin($callback, false);
        } else {
            return $this->ensureSuccessful($response);
        }
    }

    protected function sendRequest(IntegrationRequestOptionsDTO $options, array $data = []): string|array
    {
        $this->requestOptions = $options;
        $this->requestPath = $this->requestOptions->path;
        $this->requestBody = $data;

        $response = $this->executeWithRelogin(
            callback: fn(PendingRequest $request) => $this->processRequest($request),
        );

        $shouldSaveHistory = $this->requestOptions->shouldSaveHistory;
        $shouldSaveHistory = is_callable($shouldSaveHistory) ? $shouldSaveHistory($response) : $shouldSaveHistory;

        if ($shouldSaveHistory) {
            $this->saveHistory($response);
        }

        $responseCallback = $this->requestOptions->additional['response_callback'] ?? null;
        $result = $responseCallback ? $responseCallback($response) : $this->ensureSuccessful($response)->json();

        return $result ?: [];
    }

    protected function saveHistory(Response $response): void
    {
        $data = [
            'request_path' => $this->requestPath,
            'request_body' => $this->requestBody,
            'response_body' => $response->json() ?? [],
            'response_status' => $response->status(),
        ];

        $historyDataCallback = $this->requestOptions->additional['history_data_callback'] ?? null;
        $data = $historyDataCallback ? $historyDataCallback($data) : $data;

        $model = (clone $this->config->historyModel)->fill($data);
        $model->company_id =  $this->company?->id;
        $model->additional = $this->additionalData;
        $model->safelySave();
    }

    protected function throwErrorMessage(string $message): void
    {
        throw new IntegrationException($this->config->name, $message);
    }

    public function setAdditionalData(array $data): static
    {
        $this->additionalData = $data;
        return $this;
    }
}
