<?php

namespace Modules\User\Helpers\Role;

use Illuminate\Support\Facades\Route;

class RolePermissionHelper
{
    public const PERMISSION_ALL_RECORDS = 'permission-all-records';

    public const ROUTE_COMPANY_PRODUCT_BRAND = 'company.product.brand';

    public static function registerAllRecordsRoute(): void
    {
        Route::get(static::PERMISSION_ALL_RECORDS, fn() => abort(404))->name(static::PERMISSION_ALL_RECORDS);
    }
}
