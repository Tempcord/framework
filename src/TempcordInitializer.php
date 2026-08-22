<?php

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\EventsRegistry;
use Tempcord\Registries\PluginsRegistry;
use Tempcord\Runtime\CommandBinder;
use Tempcord\Runtime\CommandRegistrar;
use Tempcord\Runtime\PluginBooter;
use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;

final readonly class TempcordInitializer implements Initializer
{
    #[Singleton]
    public function initialize(Container $container): Tempcord
    {
        return new Tempcord(
            discord: $container->get(Discord::class),
            commandsRegistry: $container->get(CommandsRegistry::class),
            eventsRegistry: $container->get(EventsRegistry::class),
            pluginsRegistry: $container->get(PluginsRegistry::class),
            registrar: $container->get(CommandRegistrar::class),
            binder: $container->get(CommandBinder::class),
            pluginBooter: $container->get(PluginBooter::class),
        );
    }
}
