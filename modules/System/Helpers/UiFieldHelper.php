<?php

namespace Modules\System\Helpers;

use App\Base\Model;
use App\Helpers\ReflectionHelper;
use App\Requests\FormRequest;
use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use App\Rules\PhoneRule;
use App\Rules\UniqueRule;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class UiFieldHelper
{
    private const TYPES = [
        'integer' => 'integer',
        'numeric' => 'number',
        'decimal' => 'number',
        'boolean' => 'boolean',
        'array' => 'array',
        'json' => 'json',
        'email' => 'email',
        'url' => 'url',
        'date' => 'date',
        'image' => 'image',
    ];

    /** @var array<string, class-string> display name => enum class */
    private static array $enums = [];

    /**
     * @return array<string, array{type: string, label: string, required: bool, nullable: bool, localized: bool, max?: float, min?: float, format?: string, accept?: string[], options?: array, enum?: string, relation?: string, unique?: bool}>
     */
    public static function fields(string $formRequestClass, Model $model): array
    {
        $request = static::request($formRequestClass, $model);
        $imageFields = array_keys(static::property($request, 'imageUploads'));
        $languages = implode('|', array_keys(singletons()->system->languages->all));
        $languagesRegex = "/\.($languages)$/";
        $result = [];

        foreach ($request->rules() as $key => $rules) {
            $base = preg_replace($languagesRegex, '', $key);

            if (isset($result[$base])) continue;

            $field = static::field(key: $base, localized: $base !== $key, rules: $rules);

            if (Str::is($imageFields, $base)) $field['type'] = 'image';

            $result[$base] = $field;
        }

        return $result;
    }

    public static function label(string $key): string
    {
        return Lang::has("fields.$key") ? __("fields.$key") : $key;
    }

    public static function modelName(Model $model): string
    {
        return str_replace('\\Models\\', '.', Str::after($model::class, 'Modules\\'));
    }

    public static function property(object $object, string $name): mixed
    {
        $property = new \ReflectionProperty($object, $name);

        return $property->getValue($object);
    }

    private static function request(string $formRequestClass, Model $model): FormRequest
    {
        $reflection = new \ReflectionClass($formRequestClass);
        $request = $reflection->newInstanceWithoutConstructor();

        if ($request instanceof ResourceFormRequest) {
            $request->model = $model;
        }

        $request->__construct();

        return $request;
    }

    private static function field(string $key, bool $localized, string|array $rules): array
    {
        $field = [
            'type' => 'string',
            'label' => static::label($key),
            'required' => false,
            'nullable' => false,
            'localized' => $localized,
        ];

        foreach ((array)$rules as $rule) {
            if ($rule instanceof ExistsWithOldRule) {
                $field['type'] = 'relation';
                $field['relation'] = static::modelName(static::property($rule, 'query')->getModel());
                continue;
            }

            if ($rule instanceof UniqueRule) {
                $field['unique'] = true;
                continue;
            }

            if ($rule instanceof PhoneRule) {
                $field['type'] = 'phone';
                continue;
            }

            if (is_object($rule) && !($rule instanceof \Stringable)) continue;

            foreach (explode('|', (string)$rule) as $item) {
                static::apply($field, $item);
            }
        }

        return $field;
    }

    private static function apply(array &$field, string $rule): void
    {
        [$name, $params] = array_pad(explode(':', $rule, 2), 2, '');

        if ($name === 'mimes') $field['accept'] = explode(',', $params);

        match (true) {
            $name === 'required' => $field['required'] = true,
            $name === 'nullable' => $field['nullable'] = true,
            isset(static::TYPES[$name]) => $field['type'] = static::TYPES[$name],
            in_array($name, ['file', 'mimes', 'mimetypes'], true) && $field['type'] !== 'image' => $field['type'] = 'file',
            $name === 'date_format' => $field = [...$field, 'type' => 'datetime', 'format' => $params],
            in_array($name, ['max', 'min'], true) => $field[$name] = (float)$params,
            $name === 'between' => $field = [...$field, 'min' => (float)Str::before($params, ','), 'max' => (float)Str::after($params, ',')],
            $name === 'in' => static::applyIn($field, $params),
            default => null,
        };
    }

    private static function applyIn(array &$field, string $params): void
    {
        $values = array_map(fn(string $value) => trim($value, '"'), explode(',', $params));
        $enum = static::enumByValues($values);
        $class = $enum !== null ? static::$enums[$enum] : null;

        $field['type'] = 'select';
        $field['options'] = $class
            ? array_combine($values, array_map(fn(string $value) => $class::labelByValue($value), $values))
            : array_combine($values, $values);

        if ($enum) $field['enum'] = $enum;
    }

    private static function enumByValues(array $values): ?string
    {
        sort($values);

        foreach (static::enums() as $name => $class) {
            $enumValues = array_map('strval', $class::values());
            sort($enumValues);

            if ($enumValues === $values) return $name;
        }

        return null;
    }

    /**
     * @return array<string, class-string>
     */
    private static function enums(): array
    {
        if (!static::$enums) {
            $pattern = "/^Modules\\\|\\\\Enums\\\|Enum$/";

            foreach (glob(base_path('modules/*/Enums/*.php')) as $file) {
                $class = ReflectionHelper::classByFile($file)->getName();
                $name = preg_replace_array($pattern, ['', '.', ''], $class);

                static::$enums[$name] = $class;
            }
        }

        return static::$enums;
    }
}
