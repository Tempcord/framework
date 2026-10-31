<?php

namespace Tempcord\Compile\Concerns;

use BackedEnum;
use Tempest\Reflection\ParameterReflector;

/**
 * Reads what a handler's parameter is typed as.
 *
 * Both halves of the framework ask: the compiler to choose a Discord option
 * type, the runtime to decide what to pass. Asking a parameter with no type for
 * its type throws, so every caller needs the same guard first.
 */
trait ReadsParameterTypes
{
    /**
     * The parameter's declared type, or null when it declares none.
     */
    private function parameterType(ParameterReflector $parameter): ?string
    {
        if (!$parameter->getReflection()->hasType()) {
            return null;
        }

        return $parameter->getType()->getName();
    }

    /**
     * @phpstan-assert-if-true class-string<BackedEnum> $type
     */
    private static function isBackedEnum(?string $type): bool
    {
        return $type !== null && is_subclass_of($type, BackedEnum::class);
    }
}
