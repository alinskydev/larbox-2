<?php

return [
    'storage' => [
        'url' => env('STORAGE_URL'),
        'secret_key' => env('STORAGE_SECRET_KEY'),
    ],
    'store' => [
        'url' => env('STORE_URL'),
        'username' => env('STORE_USERNAME'),
        'password' => env('STORE_PASSWORD'),
    ],
    'telegram_bot' => [
        'url' => env('TELEGRAM_BOT_URL'),
        'dev_chat_ids' => env('TELEGRAM_BOT_DEV_CHAT_IDS'),
    ],
];
