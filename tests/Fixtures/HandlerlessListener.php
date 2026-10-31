<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Event\Event;

#[Event(name: 'READY')]
final class HandlerlessListener
{
    public function somethingElse(): void {}
}
