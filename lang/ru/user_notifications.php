<?php

use Modules\User\Enums\NotificationTypeEnum;

return [
    NotificationTypeEnum::ANNOUNCEMENT->value => [
        'label' => 'Объявление',
        'text' => ':text',
    ],
    NotificationTypeEnum::MESSAGE->value => [
        'label' => 'Сообщение',
        'text' => ':text',
    ],

    NotificationTypeEnum::EXCEL_EXPORT_COMPLETED->value => [
        'label' => 'Excel файл собран',
        'text' => 'Ваш Excel файл был успешно собран',
    ],
    NotificationTypeEnum::EXCEL_EXPORT_FAILED->value => [
        'label' => 'Ошибка при сборке Excel файла',
        'text' => 'Произошла ошибка при сборке вашего Excel файла. Текст ошибки: :message.',
    ],

    NotificationTypeEnum::EXCEL_IMPORT_COMPLETED->value => [
        'label' => 'Excel файл импортирован',
        'text' => 'Процесс импорта Excel файла окончен',
    ],
    NotificationTypeEnum::EXCEL_IMPORT_FAILED->value => [
        'label' => 'Ошибка при сборке Excel файла',
        'text' => 'Произошла ошибка при импорте вашего Excel файла. Текст ошибки: :message.',
    ],
];
