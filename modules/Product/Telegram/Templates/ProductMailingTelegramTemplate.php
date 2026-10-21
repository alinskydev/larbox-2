<?php

namespace Modules\Product\Telegram\Templates;

use App\Helpers\StringHelper;
use Illuminate\Support\Arr;
use Integrations\Telegram\Helpers\TelegramHelper;
use Modules\Company\Models\Company;
use Modules\Product\Excel\Export\Writers\ProductExcelExportWriter;

class ProductMailingTelegramTemplate
{
    public static function process(Company $company, array $products): void
    {
        $date = date('d.m.Y');
        $chats = Arr::pluck($company->credential->telegram_chats, 'chat_id');

        $text = [
            '*Company:* ' => $company->name,
            '*Total products count:* ' => count($products),
        ];

        $productGroups = array_chunk($products, 1);
        $files = [];

        foreach ($productGroups as $key => $productGroup) {
            $files[$key] = [
                'path' => storage_path('larbox/_tmp/' . StringHelper::uuid()),
                'filename' => "Products - $date.xlsx",
            ];

            $excelWriter = new ProductExcelExportWriter($productGroup);
            $excelWriter->process();
            $excelWriter->spreadsheet->save($files[$key]['path']);
        }

        static::send($chats, $text, $files);
    }

    public static function send(array $chats, array $text, array $files): void
    {
        TelegramHelper::sendMessage(
            chats: $chats,
            text: $text,
        );

        TelegramHelper::sendGroupedFiles(
            chats: $chats,
            files: $files,
        );
    }
}
