<?php

namespace Tempcord\Registries;

use Tempcord\Plugins\Plugin;
use Tempest\Container\Container;
use Tempest\Container\Singleton;

/**
 * Holds every discovered plugin, by name.
 *
 * Names rather than instances, and resolved only when the bot is about to boot:
 * discovery runs while the container is still being assembled, and a plugin
 * built there would drag whatever it depends on — the Discord connection above
 * all — into existence before the application has been configured. That turns a
 * missing token into a fatal error during discovery, which every console
 * command shares, including the one that exists to set the token up.
 */
#[Singleton]
final class PluginsRegistry
{
    /** @var array<class-string<Plugin>, class-string<Plugin>> */
    private array $plugins = [];

    public function __construct(
        private readonly ?Container $container = null,
    ) {}

    /**
     * @param class-string<Plugin> $plugin
     */
    public function add(string $plugin): void
    {
        // Keyed by class so a plugin discovered twice boots once.
        $this->plugins[$plugin] = $plugin;
    }

    /**
     * @return list<Plugin>
     */
    public function all(): array
    {
        return array_values(array_map($this->resolve(...), $this->plugins));
    }

    /**
     * What was discovered, without building any of it.
     *
     * @return list<class-string<Plugin>>
     */
    public function classes(): array
    {
        return array_values($this->plugins);
    }

    public function count(): int
    {
        return count($this->plugins);
    }

    /**
     * @param class-string<Plugin> $plugin
     */
    private function resolve(string $plugin): Plugin
    {
        /** @var Plugin */
        return $this->container?->get($plugin) ?? new $plugin();
    }
}
