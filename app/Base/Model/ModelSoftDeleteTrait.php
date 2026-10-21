<?php

namespace App\Base\Model;

use App\Base\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin Model
 */
trait ModelSoftDeleteTrait
{
    use SoftDeletes;

    public function restore()
    {
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = null;
        $this->exists = true;

        $result = static::withoutEvents(fn() => $this->save());

        if ($result) {
            $this->fireModelEvent('restored', false);
        }

        return $result;
    }
}
