<?php

namespace Tempcord;

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\Ready;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\EventsRegistry;

final class Tempcord
{
    private EventsRegistry $eventsRegistry;

    public bool $booted = false;

    public function __construct(
        public readonly Discord           $discord,
        private readonly CommandsRegistry $commandsRegistry
    )
    {
        $this->discord->registerExtension($this->commandsRegistry);
//        $this->eventsRegistry = new EventsRegistry($this->discord);

        $this->discord->gateway->events->on(Events::READY, function (Ready $ready) {
            $this->booted = true;
        });
    }


    public function boot(): void
    {
//        $this->commandsRegistry->listen(
//            console: $this->console
//        );

//        $this->eventsRegistry->listen(
//            console: $this->console
//        );

        $this->discord->gateway->open();
    }
}
