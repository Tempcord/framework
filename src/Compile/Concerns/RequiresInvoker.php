<?php

namespace Tempcord\Compile\Concerns;

use RuntimeException;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\MethodReflector;

/**
 * Finds the __invoke method an attribute on a class points to.
 *
 * An attribute on the class rather than a method means the class itself is the
 * handler. Without __invoke there is nothing to call, which is reported at
 * discovery rather than the first time somebody uses it.
 */
trait RequiresInvoker
{
    /**
     * @param string $otherwise what the class should do instead, completing
     *                          "Class [Foo] …" in the error
     */
    private function requireInvoker(ClassReflector $class, string $otherwise): MethodReflector
    {
        if (!$class->getReflection()->hasMethod('__invoke')) {
            throw new RuntimeException('Class [' . $class->getName() . '] ' . $otherwise);
        }

        return $class->getMethod('__invoke');
    }
}
