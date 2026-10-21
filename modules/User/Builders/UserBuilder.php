<?php

namespace Modules\User\Builders;

use App\Base\EloquentBuilder;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\User;

/**
 * @extends EloquentBuilder<User>
 */
class UserBuilder extends EloquentBuilder
{
    public function whereFullNameLike(string $value): static
    {
        return $this->whereHas('profile', function ($query) use ($value) {
            $query->whereIlike(DB::raw("concat(last_name, ' ', first_name)"), $value);
        });
    }
}
