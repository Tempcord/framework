<?php

namespace Tempcord;

use Ragnarok\Fenrir\Bitwise\Bitwise;

final readonly class TempcordConfig
{
    public function __construct(
        public string  $token,
        public Bitwise $intents,
    )
    {
    }
}