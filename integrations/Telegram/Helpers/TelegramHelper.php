<?php

namespace Integrations\Telegram\Helpers;

use App\Helpers\ArrayHelper;
use App\Helpers\StringHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Integrations\Telegram\Jobs\TelegramJob;
use Laravel\SerializableClosure\SerializableClosure;

class TelegramHelper
{
    public static function sendMessage(
        array $chats,
        string|array $text,
    ): void {
        if (is_array($text)) {
            $text = Arr::map($text, fn($value) => StringHelper::escapeForMarkdownV2((string)$value));
            $text = ArrayHelper::asMultilineString($text);
        }

        foreach ($chats as $chat) {
            dispatch(new TelegramJob(
                callback: new SerializableClosure(function () use ($chat, $text) {
                    Http::throw()->post(
                        url: config('integrations.telegram_bot.url') . '/sendMessage',
                        data: [
                            'parse_mode' => 'MarkdownV2',
                            'chat_id' => $chat,
                            'text' => $text,
                        ],
                    );
                }),
            ));
        }
    }

    /**
     * @param 'document'|'photo' $type
     */
    public static function sendFile(
        array $chats,
        string $file,
        string $type,
        string $caption = '',
        ?string $filename = null,
    ): void {
        $action = 'send' . ucfirst($type);
        $caption = StringHelper::escapeForMarkdownV2($caption);

        foreach ($chats as $chat) {
            dispatch(new TelegramJob(
                callback: new SerializableClosure(function () use (
                    $chat,
                    $type,
                    $file,
                    $filename,
                    $action,
                    $caption,
                ) {
                    Http::throw()->attach(
                        name: $type,
                        contents: file_get_contents($file),
                        filename: $filename,
                    )->post(
                        url: config('integrations.telegram_bot.url') . "/$action",
                        data: [
                            'parse_mode' => 'MarkdownV2',
                            'chat_id' => $chat,
                            'caption' => $caption,
                        ],
                    );
                }),
            ));
        }
    }

    /**
     * @param array<int, array{path: string, filename?: string}> $files
     */
    public static function sendGroupedFiles(
        array $chats,
        array $files,
    ): void {
        $fileGroups = array_chunk($files, 10);

        foreach ($chats as $chat) {
            foreach ($fileGroups as $fileGroup) {
                // sendMediaGroup accepts 2-10 files
                if (count($fileGroup) === 1) {
                    static::sendFile(
                        chats: [$chat],
                        file: $fileGroup[0]['path'],
                        type: 'document',
                        filename: $fileGroup[0]['filename'] ?? null,
                    );

                    continue;
                }

                dispatch(new TelegramJob(
                    callback: new SerializableClosure(function () use ($fileGroup, $chat) {
                        $media = [];
                        $request = Http::throw()->asMultipart();

                        foreach ($fileGroup as $key => $fileInfo) {
                            $media[] = [
                                'type' => 'document',
                                'media' => "attach://file{$key}",
                                'caption' => '',
                            ];

                            $request->attach(
                                name: "file{$key}",
                                contents: file_get_contents($fileInfo['path']),
                                filename: $fileInfo['filename'] ?? null,
                            );
                        }

                        $request->post(
                            url: config('integrations.telegram_bot.url') . '/sendMediaGroup',
                            data: [
                                'chat_id' => $chat,
                                'media' => json_encode($media),
                            ],
                        );
                    }),
                ));
            }
        }
    }

    public static function devChats(): array
    {
        $chats = explode(',', config('integrations.telegram_bot.dev_chat_ids'));
        $chats = array_map('trim', $chats);

        return $chats;
    }
}
