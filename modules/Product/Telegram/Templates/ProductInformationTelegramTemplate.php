<?php

namespace Modules\Product\Telegram\Templates;

use App\Helpers\NumberHelper;
use Illuminate\Support\Arr;
use Integrations\Telegram\Helpers\TelegramHelper;
use Modules\Product\Models\Product;

class ProductInformationTelegramTemplate
{
    public static function process(Product $product): void
    {
        $locale = app()->getLocale();

        $data = [
            '*Product:* ' => $product->full_text,
            '*Category:* ' => $product->category->name[$locale] ?? '',
            '*Brand:* ' => $product->brand->name,
            '*Price:* ' => $product->price ? NumberHelper::formatAmountWithCurrency($product->price->price) : '',
        ];

        TelegramHelper::sendMessage(
            chats: Arr::pluck($product->company->credential->telegram_chats, 'chat_id'),
            text: $data,
        );
    }
}
