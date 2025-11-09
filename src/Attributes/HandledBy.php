<?php

namespace Tempcord\Attributes;

use Attribute;

#[Attribute]
readonly class HandledBy
{
    public function __construct(
        public string $method,
    )
    {

    }

}