<?php

namespace Tempcord;

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\Ready;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\EventsRegistry;
use Tempcord\Registries\PluginsRegistry;
use Tempcord\Runtime\Outcome;

/**
 * The bot itself: the Discord connection plus the registries that fill it.
 */
final class Tempcord
{
    public bool $booted = false;

    public function __construct(
        public readonly Discord $discord,
        private readonly CommandsRegistry $commandsRegistry,
        private readonly EventsRegistry $eventsRegistry,
        private readonly PluginsRegistry $pluginsRegistry,
    ) {
        $this->discord->gateway->events->on(Events::READY, function (Ready $ready): void {
            $this->discord->registerExtension($this->commandsRegistry->extension);
            $this->booted = true;
        });
    }

    /**
     * @return list<Outcome>
     */
    public function registerCommands(): array
    {
        return $this->commandsRegistry->register($this->discord);
    }

    /**
     * Binds everything that has been discovered.
     *
     * Plugins boot last, so whatever they do runs against a bot whose commands
     * and events are already bound, and still before the gateway opens.
     *
     * @return list<Outcome>
     */
    public function listen(): array
    {
        return [
            ...$this->commandsRegistry->listen(),
            ...$this->eventsRegistry->listen($this->discord),
            ...$this->pluginsRegistry->boot($this),
        ];
    }

    public function boot(): void
    {
        $this->discord->gateway->open();
    }
}
