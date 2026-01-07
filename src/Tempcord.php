<?php

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use Tempcord\Plugins\PluginRegistry;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Registries\TasksRegistry;
use Tempest\Container\Container;
use Tempest\Log\Logger;

final readonly class Tempcord
{
    public function __construct(
        private(set) CommandsRegistry $commandsRegistry,
        private(set) ComponentsRegistry $componentsRegistry,
        private(set) TasksRegistry $tasksRegistry,
        private(set) PluginRegistry $pluginRegistry,
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
     * Get the plugin registry.
     */
    public function plugins(): PluginRegistry
    {
        return $this->pluginRegistry;
    }
}
