<?php

namespace Tempcord;

use CyberWolf\Discord\Discord;
use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;
use Tempest\Log\Logger;

/**
 * Makes the Discord connection a container singleton in its own right.
 *
 * The runtime resolves it — the option resolver fetches users and channels
 * through it, and a plugin may want it too — so it cannot be something only
 * Tempcord itself holds.
 */
final readonly class DiscordInitializer implements Initializer
{
    #[Singleton]
    public function initialize(Container $container): Discord
    {
        $config = $container->get(TempcordConfig::class);

        if (trim($config->token) === '') {
            throw new \RuntimeException(
                'No Discord token configured. Run "php tempcord init" to set one up, '
                . 'or add DISCORD_TOKEN to your .env file.',
            );
        }

        return new Discord(
            token: $config->token,
            logger: $container->get(Logger::class),
        )->withGateway(
            intents: $config->intents,
        )->withRest();
    }
}
