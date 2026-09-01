<?php

namespace Tempcord;

use Tempcord\Discord\Constants\Events;
use Tempcord\Discord\Discord;
use Tempcord\Discord\Gateway\Events\Ready;
use Tempcord\Cache\CacheSubscriber;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Registries\EventsRegistry;
use Tempcord\Registries\PluginsRegistry;
use Tempcord\Runtime\CommandBinder;
use Tempcord\Runtime\CommandRegistrar;
use Tempcord\Runtime\ComponentBinder;
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
        private readonly ComponentsRegistry $componentsRegistry,
        private readonly EventsRegistry $eventsRegistry,
        private readonly PluginsRegistry $pluginsRegistry,
        private readonly CommandRegistrar $registrar,
        private readonly CommandBinder $binder,
        private readonly ComponentBinder $componentBinder,
        private readonly PluginBooter $pluginBooter,
        private readonly ?CacheSubscriber $cacheSubscriber = null,
    ) {
        $this->discord->gateway->events->on(Events::READY, function (Ready $ready): void {
            $this->discord->registerExtension($this->binder->extension);
            $this->discord->registerExtension($this->componentBinder->extension);
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
     * The cache subscribes first, so a listener reading it sees the state the
     * event it is handling has already produced. Plugins boot last, so whatever
     * they do runs against a bot whose commands and events are already bound,
     * and still before the gateway opens.
     *
     * @return list<Outcome>
     */
    public function listen(): array
    {
        return [
            ...$this->subscribeCache(),
            ...$this->binder->bindAll($this->commandsRegistry->all()),
            ...$this->componentBinder->bindAll($this->componentsRegistry->all()),
            ...$this->eventsRegistry->listen($this->discord),
            ...$this->pluginBooter->bootAll($this->pluginsRegistry->all(), $this),
        ];
    }

    /**
     * @return list<Outcome>
     */
    private function subscribeCache(): array
    {
        if ($this->cacheSubscriber === null) {
            return [];
        }

        $this->cacheSubscriber->subscribe($this->discord);

        return [Outcome::success('Caching guilds, channels, roles, members and voice states.')];
    }

    public function boot(): void
    {
        $this->discord->gateway->open();
    }
}
