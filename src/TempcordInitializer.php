<?php

namespace Tempcord;

use Tempcord\Registries\CommandsRegistry;
use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;

final readonly class TempcordInitializer implements Initializer
{
    #[Singleton]
    public function initialize(Container $container): Tempcord
    {
        return new Tempcord(
            commandsRegistry: $container->get(
                className: CommandsRegistry::class
            ),
            container: $container
        );
    }
}