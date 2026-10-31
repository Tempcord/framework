<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Event\Event;

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
