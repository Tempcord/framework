<?php

declare(strict_types=1);

namespace Tempcord\Plugins;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Extension\Extension;
use Tempest\Container\Container;
use Tempest\Container\Singleton;
use Tempest\Log\Logger;

/**
 * Manages the lifecycle of Tempcord plugins.
 *
 * Plugins are discovered automatically and initialized in priority order.
 */
#[Singleton]
class Registry implements Extension
{
    /** @var array<string, Plugin> Registered plugins indexed by name */
    private array $plugins = [];

    /** @var array<Plugin> Plugin attributes pending instantiation */
    private array $pending = [];

    /** @var array<class-string> Global middleware from all plugins */
    private array $globalMiddleware = [];

    /** @var bool Whether plugins have been booted */
    private bool $booted = false;

    public function __construct(
        private readonly Container $container,
        private readonly Logger $logger,
    ) {}

    /**
     * Register a plugin from its discovery attribute.
     */
    public function register(Plugin $attribute): void
    {
        $this->pending[] = $attribute;
    }

    /**
     * Called by Fenrir when Discord is initialized.
     */
    public function initialize(Discord $discord): void
    {
        if ($this->booted) {
            return;
        }

        // Instantiate and register all pending plugins
        foreach ($this->pending as $attribute) {
            $this->instantiatePlugin($attribute);
        }

        $this->pending = [];

        // Boot all plugins
        foreach ($this->plugins as $plugin) {
            $this->bootPlugin($plugin, $discord);
        }

        $this->booted = true;

        $this->logger->info(sprintf(
            'Tempcord: Loaded %d plugin(s): %s',
            count($this->plugins),
            implode(', ', array_keys($this->plugins))
        ));
    }

    /**
     * Instantiate a plugin from its attribute.
     */
    private function instantiatePlugin(Plugin $attribute): void
    {
        $className = $attribute->getPluginClass();

        try {
            /** @var Plugin $plugin */
            $plugin = $this->container->get($className);

            $name = $plugin->name();

            if (isset($this->plugins[$name])) {
                $this->logger->warning("Plugin '{$name}' is already registered, skipping duplicate");
                return;
            }

            // Let plugin register its services
            $plugin->register($this->container);

            // Collect global middleware
            $this->globalMiddleware = array_merge(
                $this->globalMiddleware,
                $plugin->middleware()
            );

            $this->plugins[$name] = $plugin;

            $this->logger->debug("Registered plugin: {$name} v{$plugin->version()}");
        } catch (\Throwable $e) {
            $this->logger->error("Failed to instantiate plugin {$className}: {$e->getMessage()}");
        }
    }

    /**
     * Boot a plugin with the Discord instance.
     */
    private function bootPlugin(Plugin $plugin, Discord $discord): void
    {
        try {
            $plugin->boot($discord);
        } catch (\Throwable $e) {
            $this->logger->error("Failed to boot plugin {$plugin->name()}: {$e->getMessage()}");
        }
    }

    /**
     * Get all registered plugins.
     *
     * @return array<string, Plugin>
     */
    public function all(): array
    {
        return $this->plugins;
    }

    /**
     * Get a specific plugin by name.
     */
    public function get(string $name): ?Plugin
    {
        return $this->plugins[$name] ?? null;
    }

    /**
     * Check if a plugin is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    /**
     * Get all global middleware from plugins.
     *
     * @return array<class-string>
     */
    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * Get all discovery namespaces from plugins.
     *
     * @return array<string>
     */
    public function getDiscoveryNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->plugins as $plugin) {
            $namespaces = array_merge($namespaces, $plugin->discoveryNamespaces());
        }

        return array_unique($namespaces);
    }
}
