<?php

namespace Tempcord;

use Tempcord\Plugins\PluginRegistry;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Registries\TasksRegistry;
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
            tasksRegistry: $container->get(TasksRegistry::class),
            pluginRegistry: $container->get(PluginRegistry::class),
            container: $container,
        );
    }
}