<?php

namespace Modules\System\Models;

use App\Base\Model;
use App\Casts\LocalStorage\AsImage;
use Modules\System\Observers\LanguageObserver;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|array $image
 * @property bool $is_active
 * @property bool $is_main
 * @property string $created_at
 * @property string $updated_at
 */
class Language extends Model
{
    protected $table = 'system_language';

    protected $casts = [
        'image' => AsImage::class . ':scaleDown_25|scaleDown_100|scaleDown_500',
    ];

    protected static function booted(): void
    {
        self::observe([
            LanguageObserver::class,
        ]);
    }
}
