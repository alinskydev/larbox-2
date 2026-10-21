<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('company_credential')->insert([
            [
                'company_id' => 1,
                'telegram_chats' => json_encode([
                    [
                        'name' => 'Name 1',
                        'chat_id' => 1,
                    ],
                    [
                        'name' => 'Name 2',
                        'chat_id' => 2,
                    ],
                ]),
                'store' => Crypt::encryptString(json_encode([
                    'username' => 'username',
                    'password' => 'password',
                ])),
            ],
            [
                'company_id' => 2,
                'telegram_chats' => json_encode([]),
                'store' => Crypt::encryptString(json_encode([
                    'username' => null,
                    'password' => null,
                ])),
            ],
        ]);
    }
};
