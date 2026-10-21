<?php

namespace Integrations\Store\Services;

use App\Integration\DTOs\IntegrationServiceConfigDTO;
use App\Integration\IntegrationService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\History\Models\StoreHttpLog;
use Modules\Integration\Models\Token;

class StoreBaseService extends IntegrationService
{
    protected function getConfig(): IntegrationServiceConfigDTO
    {
        if ($this->company !== null) {
            $username = $this->company->credential->store['username'];
            $password = $this->company->credential->store['password'];

            $hasOwnAccount = $username && $password;
        } else {
            $hasOwnAccount = false;
        }

        return new IntegrationServiceConfigDTO(
            name: 'Store',
            url: config('integrations.store.url'),
            username: $hasOwnAccount ? $username : config('integrations.store.username'),
            password: $hasOwnAccount ? $password : config('integrations.store.password'),
            hasOwnAccount: $hasOwnAccount,
            historyModel: new StoreHttpLog(),
        );
    }

    protected function headers(): array
    {
        $token = Token::query()
            ->where('service_name', $this->config->name)
            ->where('username', $this->config->username)
            ->first()
            ?->token;

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json; charset=utf-8',
            'Accept-Language' => $this->language,
            'Authorization' => "Bearer $token",
        ];
    }

    protected function login(): void
    {
        $response = Http::baseUrl($this->config->url)
            ->post('auth/login', [
                'username' => $this->config->username,
                'password' => $this->config->password,
            ]);

        if ($response->status() === 200) {
            Token::query()
                ->updateOrCreate([
                    'service_name' => $this->config->name,
                    'username' => $this->config->username,
                ], [
                    'token' => $response->json('token'),
                ]);
        }
    }

    protected function processRequest(PendingRequest $request): Response
    {
        $method = mb_strtolower($this->requestOptions->method);
        return $request->{$method}($this->requestPath, $this->requestBody);
    }

    protected function ensureSuccessful(Response $response): Response
    {
        if (!$response->successful()) {
            $responseBody = $response->json() ?? [];
            $errorMessage = $responseBody['message'] ?? $response->status();

            $this->throwErrorMessage($errorMessage);
        }

        return $response;
    }
}
