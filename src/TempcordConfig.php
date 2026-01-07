<?php

namespace Tempcord;

use Ragnarok\Fenrir\Bitwise\Bitwise;
use Tempcord\Support\Responses\Decorators\Branding;

final readonly class TempcordConfig
{
    public function __construct(
        public string  $token,
        public Bitwise $intents,
        public ?string $guildId = null,
        public array   $globalMiddleware = [],
        public ?Branding $branding = null
    )
    {
    }
}