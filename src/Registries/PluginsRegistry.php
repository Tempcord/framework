<?php

namespace Tempcord\Registries;

use Tempcord\Plugins\Plugin;
use Tempest\Container\Singleton;

/**
 * Holds every discovered plugin.
 *
 * Storage only, for the same reason CommandsRegistry is: discovery builds this
 * while the container is still being assembled, so it must not depend on
 * anything an initializer provides. Booting lives in PluginBooter.
 */
#[Singleton]
final class PluginsRegistry
{
    /** @var array<class-string<Plugin>, Plugin> */
    private array $plugins = [];

    public function add(Plugin $plugin): void
    {
        // Keyed by class so a plugin discovered twice boots once.
        $this->plugins[$plugin::class] = $plugin;
    }

    /**
     * @return list<Plugin>
     */
    public function all(): array
    {
        return array_values($this->plugins);
    }

    public function count(): int
    {
        return count($this->plugins);
    }
}
