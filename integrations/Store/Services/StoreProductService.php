<?php

namespace Integrations\Store\Services;

use App\Integration\DTOs\IntegrationRequestOptionsDTO;
use Illuminate\Http\Client\Response;
use Modules\System\Enums\DailyLimitActionTypeEnum;
use Modules\System\Enums\DailyLimitEntityTypeEnum;
use Modules\System\Enums\DailyLimitIntegrationActionNameEnum;
use Modules\System\Helpers\DailyLimitHelper;

class StoreProductService extends StoreBaseService
{
    protected function ensureDailyLimit(): void
    {
        DailyLimitHelper::ensure(
            entityType: DailyLimitEntityTypeEnum::COMPANY,
            entityId: $this->company->id,
            actionType: DailyLimitActionTypeEnum::INTEGRATION,
            actionName: DailyLimitIntegrationActionNameEnum::STORE_PRODUCT->value,
        );
    }

    public function list(): array
    {
        return $this->sendRequest(
            options: new IntegrationRequestOptionsDTO(
                method: 'GET',
                path: 'product/list',
                shouldSaveHistory: fn(Response $response) => $response->status() !== 200,
            ),
        );
    }

    public function create(array $data): array
    {
        $this->ensureDailyLimit();

        return $this->sendRequest(
            options: new IntegrationRequestOptionsDTO(
                method: 'POST',
                path: 'product/create',
                shouldSaveHistory: true,
            ),
            data: $data,
        );
    }

    public function update(int $id, array $data): array
    {
        return $this->sendRequest(
            options: new IntegrationRequestOptionsDTO(
                method: 'POST',
                path: "product/update/$id",
                shouldSaveHistory: true,
            ),
            data: $data,
        );
    }

    public function delete(int $id): array
    {
        return $this->sendRequest(
            options: new IntegrationRequestOptionsDTO(
                method: 'POST',
                path: "product/delete/$id",
                shouldSaveHistory: true,
            ),
        );
    }
}
