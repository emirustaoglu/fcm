<?php

declare(strict_types=1);

namespace emirustaoglu\fcm\Tests;

use emirustaoglu\fcm\Message;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    public function testTokenMessage(): void
    {
        $message = Message::create()
            ->token('token-123')
            ->notification('Başlık', 'Mesaj')
            ->data(['order_id' => 123]);

        self::assertSame([
            'token' => 'token-123',
            'notification' => [
                'title' => 'Başlık',
                'body' => 'Mesaj',
            ],
            'data' => [
                'order_id' => '123',
            ],
        ], $message->toArray());
    }

    public function testTopicMessage(): void
    {
        self::assertSame(
            [
                'topic' => 'news',
                'notification' => [
                    'title' => 'Duyuru',
                    'body' => 'Test',
                ],
            ],
            Message::create()
                ->topic('news')
                ->notification('Duyuru', 'Test')
                ->toArray()
        );
    }
}