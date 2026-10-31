<?php

namespace Tempcord\Declare\Event;

use Attribute;

/**
 * Declares an invokable class as a listener for a Discord gateway event.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Event
{
    public function __construct(
        public string $name,
    ) {}
}
