<?php

namespace Tempcord\Interfaces;

use Tempest\Reflection\MethodReflector;

interface HasHandlerDefined
{
    public ?MethodReflector $handler {
        get;
    }

}