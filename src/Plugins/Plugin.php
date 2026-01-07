<?php

declare(strict_types=1);

namespace Tempcord\Plugins;

use Ragnarok\Fenrir\Discord;
use Tempest\Container\Container;

/**
 * Interface for Tempcord plugins.
 *
 * Plugins are auto-discovered packages that extend Tempcord's functionality.
 * They can provide commands, components, tasks, middleware, and event handlers.
 */
interface Plugin
{
    /**
     * Get the plugin's unique identifier.
     *
     * @return string A unique name like "tempcord/common"
     */
    public function name(): string;

    /**
     * Get the plugin's version.
     */
    public function version(): string;

    /**
     * Get the plugin's description.
     */
    public function description(): string;

    /**
     * Called when the plugin is registered with the framework.
     *
     * Use this to register services, middleware, or configuration.
     */
    public function register(Container $container): void;

    /**
     * Called when the Discord gateway is initialized.
     *
     * Use this to set up event listeners or perform Discord-specific setup.
     */
    public function boot(Discord $discord): void;

    /**
     * Get global middleware classes provided by this plugin.
     *
     * @return array<class-string> Array of CommandMiddleware class names
     */
    public function middleware(): array;

    /**
     * Get the namespaces this plugin provides for discovery.
     *
     * Commands, components, and tasks in these namespaces will be auto-discovered.
     *
     * @return array<string> Array of namespace prefixes
     */
    public function discoveryNamespaces(): array;
}
