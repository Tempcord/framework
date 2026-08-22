<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Attributes\Command;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Definitions\CommandDefinition;
use Tempest\Reflection\ClassReflector;

abstract class TestCase extends BaseTestCase
{
    /**
     * Compiles a fixture the same way CommandsDiscovery does.
     */
    protected function definition(string $class): CommandDefinition
    {
        $reflector = new ClassReflector($class);

        /** @var Command $attribute */
        $attribute = $reflector->getAttribute(Command::class);

        return new CommandCompiler()->compile($reflector, $attribute);
    }
}
