<?php

namespace Tempcord;

use CyberWolf\Discord\Bitwise\Bitwise;

final readonly class TempcordConfig
{
    public function __construct(
        public string  $token,
        public Bitwise $intents,
    )
    {
    }
}