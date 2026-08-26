<?php

namespace Tempcord\Compiler;

use RuntimeException;
use Tempcord\Attributes\Event;
use Tempcord\Definitions\EventDefinition;
use Tempest\Reflection\ClassReflector;

final readonly class EventCompiler
{
    public function compile(ClassReflector $class, Event $event): EventDefinition
    {
        if (!$class->getReflection()->hasMethod('__invoke')) {
            throw new RuntimeException(
                'Class [' . $class->getName() . '] should declare an __invoke method',
            );
        }

        return new EventDefinition(
            name: $event->name,
            listener: $class->getName(),
            method: $class->getMethod('__invoke'),
        );
    }
}
