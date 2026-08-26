<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Event;

#[Event(name: 'READY')]
final class HandlerlessListener
{
    public function somethingElse(): void {}
}
