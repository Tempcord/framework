<?php

namespace Tempcord;

use Tempcord\Discord\Discord;
use Tempcord\Cache\CacheSubscriber;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Registries\EventsRegistry;
use Tempcord\Registries\PluginsRegistry;
use Tempcord\Registries\ScheduledTasksRegistry;
use Tempcord\Runtime\CommandBinder;
use Tempcord\Runtime\CommandRegistrar;
use Tempcord\Runtime\ComponentBinder;
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
            componentsRegistry: $container->get(ComponentsRegistry::class),
            eventsRegistry: $container->get(EventsRegistry::class),
            pluginsRegistry: $container->get(PluginsRegistry::class),
            scheduledTasksRegistry: $container->get(ScheduledTasksRegistry::class),
            registrar: $container->get(CommandRegistrar::class),
            binder: $container->get(CommandBinder::class),
            componentBinder: $container->get(ComponentBinder::class),
            pluginBooter: $container->get(PluginBooter::class),
            cacheSubscriber: $container->get(TempcordConfig::class)->cache
                ? $container->get(CacheSubscriber::class)
                : null,
        );
    }
}
