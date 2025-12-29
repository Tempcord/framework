<?php

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use Tempcord\Registries\CommandsRegistry;
use Tempest\Container\Container;
use Tempest\Log\Logger;

final readonly class Tempcord
{
    public function __construct(
        private(set) CommandsRegistry $commandsRegistry,
        private(set) Container $container
    )
    {
    }

    public function boot(Logger $logger, TempcordConfig $config): void
    {
        $discord = new Discord(
            token: $config->token,
            logger: $logger,
        )->withGateway(
            intents: $config->intents
        )->withRest();

        $this->container->singleton(Discord::class, $discord);

        $discord->registerExtension($this->commandsRegistry);

        $discord->gateway->open();
    }
}
