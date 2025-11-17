<?php

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use Tempcord\Registries\CommandsRegistry;

final readonly class Tempcord
{
    public function __construct(
        public Discord           $discord,
        private CommandsRegistry $commandsRegistry
    )
    {
        $this->discord->registerExtension($this->commandsRegistry);
    }

    public function boot(): void
    {
        $this->discord->gateway->open();
    }
}
