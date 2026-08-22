<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Attributes\Command;
use Tempest\Reflection\ClassReflector;

abstract class TestCase extends BaseTestCase
{
    /**
     * Resolves the #[Command] attribute off a fixture class the same way
     * CommandsDiscovery does: read the attribute, then attach the reflector.
     */
    protected function command(string $class): Command
    {
        $reflector = new ClassReflector($class);

        /** @var Command $command */
        $command = $reflector->getAttribute(Command::class);
        $command->reflector = $reflector;

        return $command;
    }
}
