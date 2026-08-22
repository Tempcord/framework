<?php

namespace Tempcord;

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\Ready;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\EventsRegistry;
use Tempcord\Registries\PluginsRegistry;
use Tempcord\Runtime\CommandBinder;
use Tempcord\Runtime\CommandRegistrar;
use Tempcord\Runtime\Outcome;
use Tempcord\Runtime\PluginBooter;

/**
 * The bot itself: the Discord connection, the registries that were filled
 * during discovery, and the runtime that acts on them.
 */
final class Tempcord
{
    public bool $booted = false;

    public function __construct(
        public readonly Discord $discord,
        private readonly CommandsRegistry $commandsRegistry,
        private readonly EventsRegistry $eventsRegistry,
        private readonly PluginsRegistry $pluginsRegistry,
        private readonly CommandRegistrar $registrar,
        private readonly CommandBinder $binder,
        private readonly PluginBooter $pluginBooter,
    ) {
        $this->discord->gateway->events->on(Events::READY, function (Ready $ready): void {
            $this->discord->registerExtension($this->binder->extension);
            $this->booted = true;
        });
    }

    /**
     * @return list<Outcome>
     */
    public function registerCommands(): array
    {
        return $this->registrar->register($this->discord, $this->commandsRegistry->all());
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
            ...$this->binder->bindAll($this->commandsRegistry->all()),
            ...$this->eventsRegistry->listen($this->discord),
            ...$this->pluginBooter->bootAll($this->pluginsRegistry->all(), $this),
        ];
    }

    public function boot(): void
    {
        $this->discord->gateway->open();
    }
}
