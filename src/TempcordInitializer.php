<?php

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\EventsRegistry;
use Tempcord\Registries\PluginsRegistry;
use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;
use Tempest\Log\Logger;

final readonly class TempcordInitializer implements Initializer
{
    #[Singleton]
    public function initialize(Container $container): Tempcord
    {
        $config = $container->get(TempcordConfig::class);

        return new Tempcord(
            discord: new Discord(
                token: $config->token,
                logger: $container->get(Logger::class),
            )->withGateway(
                intents: $config->intents,
            )->withRest(),
            commandsRegistry: $container->get(CommandsRegistry::class),
            eventsRegistry: $container->get(EventsRegistry::class),
            pluginsRegistry: $container->get(PluginsRegistry::class),
        );
    }
}
