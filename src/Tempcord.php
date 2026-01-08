<?php

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use React\EventLoop\Loop;
use Tempcord\Plugins\Registry as PluginRegistry;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Tasks\Registry as TasksRegistry;
use Tempest\Container\Container;
use Tempest\Log\Logger;

final readonly class Tempcord
{
    public function __construct(
        private(set) CommandsRegistry   $commandsRegistry,
        private(set) ComponentsRegistry $componentsRegistry,
        private(set) TasksRegistry      $tasksRegistry,
        private(set) PluginRegistry     $pluginRegistry,
        private(set) Container          $container
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

        // Register plugins first (they may add middleware or services)
        $discord->registerExtension($this->pluginRegistry);

        // Register command handlers
        $discord->registerExtension($this->commandsRegistry);

        // Register component handlers (buttons, select menus, modals)
        $discord->registerExtension($this->componentsRegistry);

        // Register scheduled tasks
        $discord->registerExtension($this->tasksRegistry);

        $discord->gateway->open();
    }

    /**
     * Prepare Discord instance for interactive mode without opening the gateway.
     *
     * This method creates and configures the Discord instance, registers all extensions,
     * but doesn't open the gateway connection. The gateway should be opened separately
     * by the InteractiveSession to integrate with the ReactPHP event loop.
     *
     * @param Logger $logger
     * @param TempcordConfig $config
     * @return Discord
     */
    public function prepareDiscord(Logger $logger, TempcordConfig $config): Discord
    {
        $discord = new Discord(
            token: $config->token,
            logger: $logger,
        )->withGateway(
            intents: $config->intents
        )->withRest();

        $this->container->singleton(Discord::class, $discord);

        // Register plugins first (they may add middleware or services)
        $discord->registerExtension($this->pluginRegistry);

        // Register command handlers
        $discord->registerExtension($this->commandsRegistry);

        // Register component handlers (buttons, select menus, modals)
        $discord->registerExtension($this->componentsRegistry);

        // Register scheduled tasks
        $discord->registerExtension($this->tasksRegistry);

        return $discord;
    }

    /**
     * Gracefully shutdown the Discord bot.
     *
     * Closes the gateway connection and stops the ReactPHP event loop.
     */
    public function shutdown(): void
    {
        try {
            $discord = $this->container->get(Discord::class);
            $discord->gateway->close();
        } catch (\Throwable $e) {
            // Discord instance may not exist yet
        }

        Loop::get()->stop();
    }

    /**
     * Reconnect the Discord gateway.
     *
     * Closes and reopens the gateway connection. Useful for soft reboots.
     */
    public function reconnect(): void
    {
        $discord = $this->container->get(Discord::class);
        $discord->gateway->close();
        $discord->gateway->open();
    }

    /**
     * Get the plugin registry.
     */
    public function plugins(): Registry
    {
        return $this->pluginRegistry;
    }
}
