<?php

namespace Tempcord;

use Tempcord\Plugins\Registry as PluginsRegistry;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\ComponentsRegistry;
use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;

final readonly class TempcordInitializer implements Initializer
{
    #[Singleton]
    public function initialize(Container $container): Tempcord
    {
        return new Tempcord(
            commandsRegistry: $container->get(CommandsRegistry::class),
            componentsRegistry: $container->get(ComponentsRegistry::class),
            pluginRegistry: $container->get(PluginsRegistry::class),
            container: $container,
        );
    }
}