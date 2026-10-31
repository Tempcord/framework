<?php

namespace Tempcord\Tests\Fixtures;

use RuntimeException;
use Tempcord\Declare\Event\Event;

#[Event(name: 'READY')]
final class ThrowingListener
{
    public function __invoke(object $payload): void
    {
        throw new RuntimeException('nope');
    }
}
