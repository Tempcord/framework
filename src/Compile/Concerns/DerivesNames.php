<?php

namespace Tempcord\Compile\Concerns;

use BackedEnum;
use Tempest\Reflection\ClassReflector;
use function Tempest\Support\str;

/**
 * Turns what PHP calls something into what Discord will accept.
 *
 * Discord wants lower snake_case for command, option and component names, and
 * rejects anything else. Names come from the attribute when it gives one and
 * from the PHP class, method or parameter otherwise.
 */
trait DerivesNames
{
    /**
     * A name as an attribute declared it, which may be a backed enum case.
     */
    private function declaredName(string|BackedEnum $name): string
    {
        return $name instanceof BackedEnum ? (string) $name->value : $name;
    }

    private function snakeName(string $name): string
    {
        return str($name)->snake('_')->lower()->toString();
    }

    /**
     * The class's short name with affixes that describe its kind stripped, so
     * `PingCommand` and `CommandPing` are both `ping`.
     *
     * @param list<string> $affixes
     */
    private function nameFromClass(ClassReflector $class, array $affixes): string
    {
        $name = str($class->getShortName());

        foreach ($affixes as $affix) {
            $name = $name->replaceEnd($affix, '')->replaceStart($affix, '');
        }

        return $this->snakeName($name->toString());
    }
}
