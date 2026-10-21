<?php

namespace App\Base;

use Illuminate\Support\Arr;

/**
 * @mixin \UnitEnum
 *
 * @property int|string $value
 * @method static array labels()
 */
trait EnumTrait
{
    public static function names(): array
    {
        return array_column(static::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }

    public static function fillValuesWith(mixed $value): array
    {
        return array_fill_keys(static::values(), $value);
    }

    public static function pluck(): array
    {
        return Arr::pluck(static::cases(), 'value', 'name');
    }

    public static function labelByValue(mixed $value): string
    {
        return static::labels()[$value] ?? ucfirst($value);
    }

    public static function caseByValue(mixed $value): static
    {
        $cases = static::cases();

        foreach ($cases as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new \Exception("Case doesn't exist");
    }
}
