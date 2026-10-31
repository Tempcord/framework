<?php

namespace Tempcord;

use Tempcord\Discord\Discord;
use Tempcord\Support\Cache\CacheSubscriber;
use Tempcord\Compile\Command\CommandsRegistry;
use Tempcord\Compile\Component\ComponentsRegistry;
use Tempcord\Compile\Event\EventsRegistry;
use Tempcord\Compile\Plugin\PluginsRegistry;
use Tempcord\Runtime\Command\CommandBinder;
use Tempcord\Runtime\Registration\CommandRegistrar;
use Tempcord\Runtime\Component\ComponentBinder;
use Tempcord\Runtime\Plugin\PluginBooter;
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
