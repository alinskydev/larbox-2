<?php

namespace Http\Emulator\Telegram\Tests;

class TelegramTest extends _TestCase
{
    public function test_send_message(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'sendMessage',
        );
    }

    public function test_send_document(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'sendDocument',
        );
    }

    public function test_send_photo(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'sendPhoto',
        );
    }

    public function test_send_media_group(): void
    {
        $this->sendRequest(
            method: 'POST',
            path: 'sendMediaGroup',
        );
    }
}
