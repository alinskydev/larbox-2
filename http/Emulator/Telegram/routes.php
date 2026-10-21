<?php

use Http\Emulator\Telegram\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

Route::prefix('telegram')
    ->group(function () {
        Route::post('sendMessage', [TelegramController::class, 'sendMessage'])->name('sendMessage');
        Route::post('sendDocument', [TelegramController::class, 'sendDocument'])->name('sendDocument');
        Route::post('sendPhoto', [TelegramController::class, 'sendPhoto'])->name('sendPhoto');
        Route::post('sendMediaGroup', [TelegramController::class, 'sendMediaGroup'])->name('sendMediaGroup');
    });
