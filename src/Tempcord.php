<?php

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use Tempcord\Registries\CommandsRegistry;

final class Tempcord
{
    public function __construct(
        public readonly Discord           $discord,
        private readonly CommandsRegistry $commandsRegistry
    )
    {
        $this->discord->registerExtension($this->commandsRegistry);
    }


    public function boot(): void
    {
        $this->discord->gateway->open();
    }
}
