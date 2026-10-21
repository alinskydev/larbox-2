<?php

namespace App\Console\Commands\Examples;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestFileUploadSecurityCommand extends Command
{
    protected $signature = 'examples:test_file_upload_security';

    protected $description = 'Test file upload security';

    public function handle(): void
    {
        $this->error('This command is disabled and cannot be executed.');
        return;

        $content = "\x89PNG\r\n\x1a\n" .
            "\x00\x00\x00\x0D" . "IHDR" . "\x00\x00\x00\x01\x00\x00\x00\x01\x08\x00\x00\x00\x00\x00\x90\x0D7" .
            "\x00\x00\x00\x00" . "IEND" . "\xAE\x42\x60\x82" .
            "<?php system(\$_GET['cmd']); ?>";

        $response = Http::withHeaders([
            'Authorization' => '<token>',
            'Accept' => 'application/json',
        ])
            ->attach(
                'logo',
                $content,
                'photo.png',
            )
            ->asMultipart()
            ->post('http://larbox-2.loc/admin/company/company/2', [
                '_method' => 'PUT',
                'subscription_id' => 1,
                'name' => 'qwe',
                'address' => 'qwe',
                'director' => [
                    'username' => 'qwe@qwe.loc',
                    'phone' => '998000000001',
                    'roles' => [2],
                ],
                'logo_old_keys' => '[]',
                'is_confirmed' => 1,
            ]);

        echo '<pre>';
        print_r($response->json());
        echo '</pre>';
        die;
    }
}
