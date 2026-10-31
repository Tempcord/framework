<?php

namespace Tempcord\Compile\Event;

use Tempest\Reflection\MethodReflector;

/**
 * A gateway event name paired with the invokable that answers it.
 */
final readonly class EventDefinition
{
    public function __construct(
        public string $name,
        public string $listener,
        public MethodReflector $method,
    ) {}
}
