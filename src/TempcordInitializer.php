<?php

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use Tempest\Console\Console;
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
                intents: $config->intents
            )->withRest(),
            console: $container->get(Console::class)
        );
    }
}