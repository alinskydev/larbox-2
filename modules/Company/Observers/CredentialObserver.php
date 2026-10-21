<?php

namespace Modules\Company\Observers;

use App\Helpers\ArrayHelper;
use Modules\Company\Models\Credential;

class CredentialObserver
{
    public function saving(Credential $model): void
    {
        $this->savingDefaults($model);
        $this->savingTelegramChats($model);
    }

    private function savingDefaults(Credential $model): void
    {
        $fields = [
            'store',
        ];

        foreach ($fields as $field) {
            $result = ArrayHelper::fillByTemplate(
                data: $model->$field ?? [],
                template: [
                    'username' => null,
                    'password' => null,
                ],
            );

            $result = array_map(fn($value) => !empty($value) ? (string)$value : null, $result);

            $model->$field = $result;
        }
    }

    private function savingTelegramChats(Credential $model): void
    {
        $telegramChats = $model->telegram_chats ?? [];

        foreach ($telegramChats as &$chat) {
            $chat = ArrayHelper::fillByTemplate(
                data: $chat,
                template: [
                    'name' => null,
                    'chat_id' => -1,
                ],
            );

            $chat['chat_id'] = (int)$chat['chat_id'];
        }

        $model->telegram_chats = $telegramChats;
    }
}
