<?php

namespace Tempcord\Compile\Event;

use Tempcord\Compile\Concerns\RequiresInvoker;
use Tempcord\Declare\Event\Event;
use Tempest\Reflection\ClassReflector;

final readonly class EventCompiler
{
    use RequiresInvoker;

    public function compile(ClassReflector $class, Event $event): EventDefinition
    {
        return new EventDefinition(
            name: $event->name,
            listener: $class->getName(),
            method: $this->requireInvoker($class, 'should declare an __invoke method'),
        );
    }
}
