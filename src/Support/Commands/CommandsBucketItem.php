<?php

namespace Tempcord\Support\Commands;

use Tempcord\Attributes\Command;
use Tempest\Reflection\ClassReflector;

class CommandsBucketItem
{
    public function __construct(
        protected(set) Command $command,
        protected(set) ClassReflector $reflector
    )
    {

    }

}