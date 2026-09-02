<?php

namespace Tempcord\Compiler;

use RuntimeException;
use Tempcord\Attributes\Scheduled;
use Tempcord\Definitions\ScheduledTaskDefinition;
use Tempest\Reflection\ClassReflector;

final readonly class ScheduledTaskCompiler
{
    public function compile(ClassReflector $class, Scheduled $scheduled): ScheduledTaskDefinition
    {
        if (!$class->getReflection()->hasMethod('__invoke')) {
            throw new RuntimeException(
                'Class [' . $class->getName() . '] should declare an __invoke method',
            );
        }

        /*
         * An interval of zero asks the loop to run the task as fast as it can,
         * which starves everything else including the gateway heartbeat. That
         * is never what someone meant to write.
         */
        if ($scheduled->everySeconds <= 0) {
            throw new RuntimeException(
                'Scheduled task [' . $class->getName() . '] must run at an interval greater than zero.',
            );
        }

        return new ScheduledTaskDefinition(
            task: $class->getName(),
            everySeconds: $scheduled->everySeconds,
            method: $class->getMethod('__invoke'),
        );
    }
}
