<?php

namespace Tempcord\Definitions;

use Tempest\Reflection\MethodReflector;

/**
 * A piece of recurring work paired with how often it runs.
 */
final readonly class ScheduledTaskDefinition
{
    public function __construct(
        public string $task,
        public float $everySeconds,
        public MethodReflector $method,
    ) {}
}
