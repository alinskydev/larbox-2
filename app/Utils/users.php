<?php

use Modules\Company\Models\Company;
use Modules\User\Models\User;

if (!function_exists('user')) {
    function user(): ?User
    {
        return request()->user();
    }
}

if (!function_exists('setUser')) {
    function setUser(User|int|null $user, bool $withTrashed = false): ?User
    {
        if ($user !== null && !($user instanceof User)) {
            $user = User::query()
                ->when($withTrashed, function ($query) {
                    $query->withTrashed();
                })
                ->findOrFail($user);
        }

        request()->setUserResolver(fn() => $user);

        return $user;
    }
}

if (!function_exists('setUserAsCompanyDirector')) {
    function setUserAsCompanyDirector(Company|int $company, bool $withTrashed = false): User
    {
        if (!($company instanceof Company)) {
            $company = Company::query()
                ->when($withTrashed, function ($query) {
                    $query->withTrashed();
                })
                ->findOrFail($company);
        }

        $user = $company->director;
        request()->setUserResolver(fn() => $user);

        return $user;
    }
}
