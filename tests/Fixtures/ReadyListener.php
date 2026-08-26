<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Event;

#[Event(name: 'READY')]
final class ReadyListener
{
    /** @var list<object> */
    public static array $received = [];

    public function __invoke(object $payload): void
    {
        self::$received[] = $payload;
    }
}
