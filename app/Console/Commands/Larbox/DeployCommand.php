<?php

namespace App\Console\Commands\Larbox;

use App\Base\Model;
use App\Helpers\StringHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Integrations\Telegram\Helpers\TelegramHelper;
use Modules\System\Enums\EventTypeEnum;

class DeployCommand extends Command
{
    protected $signature = 'larbox:deploy';

    protected $description = 'Will be executed after deploy';

    protected string $systemEventConnection = 'main';

    public function handle(): void
    {
        try {
            $this->runDeployScripts();

            Cache::tags(['db_schema'])->flush();

            Cache::forget('system_languages');
            Cache::forget('system_settings');
            Cache::tags('system_public_data')->flush();

            Artisan::call('optimize');
            Artisan::call('queue:restart');

            $this->sendMessageToTelegram('✅ Successful deploy');
        } catch (\Throwable $e) {
            Artisan::call('optimize:clear');

            $this->sendMessageToTelegram('❌ Error in deploy', $e->getMessage() . "\n\n" . $e->getTraceAsString());
        }
    }

    private function runDeployScripts(): void
    {
        $files = glob(base_path('app/Console/Commands/Deploy/*.php'));

        foreach ($files as $file) {
            $fileName = File::basename($file);

            $isScriptExecuted = DB::connection($this->systemEventConnection)
                ->table('system_event')
                ->where('project', config('app.name'))
                ->where('type', EventTypeEnum::DEPLOY->value)
                ->where(DB::raw("additional->>'file'"), $fileName)
                ->exists();

            if (!$isScriptExecuted) {
                (new Model())->safelyDBProcess(function () use ($file, $fileName) {
                    (require $file)->run();

                    DB::connection($this->systemEventConnection)
                        ->table('system_event')
                        ->insert([
                            'project' => config('app.name'),
                            'type' => EventTypeEnum::DEPLOY->value,
                            'additional' => json_encode([
                                'file' => $fileName,
                            ]),
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                });
            }
        }
    }

    private function sendMessageToTelegram(string $title, ?string $description = null): void
    {
        if (!($chats = TelegramHelper::devChats())) return;

        $title = StringHelper::escapeForMarkdownV2($title);
        $url = StringHelper::escapeForMarkdownV2(config('app.url'));
        $text = "*$title* \\| $url";

        if ($description) {
            $description = StringHelper::escapeForMarkdownV2(Str::limit($description, 2000));
            $text .= "\n```\n$description\n```";
        }

        TelegramHelper::sendMessage(
            chats: $chats,
            text: $text,
        );
    }
}
