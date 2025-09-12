<?php

namespace Tempcord\Attributes;

use Attribute;
use RuntimeException;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\MethodReflector;

#[Attribute]
class Event
{
    public ClassReflector $reflector;

    public MethodReflector $handler {
        get {
            if (!$this->reflector->getReflection()->hasMethod('__invoke')) {
                throw new RuntimeException('Class [' . $this->reflector->getName() . '] should declare have an __invoke method');
            }

            return $this->reflector->getMethod('__invoke');
        }
    }

    public function __construct(
        public string $name,
    )
    {
    }
}