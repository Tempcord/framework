<?php

namespace Tempcord\Registries;

use Tempest\Log\Logger;
use Tempcord\Plugins\Plugin;
use Tempcord\Runtime\Outcome;
use Tempcord\Tempcord;
use Tempest\Container\Singleton;
use Throwable;

/**
 * Holds every discovered plugin and boots them before the gateway opens.
 */
#[Singleton]
final class PluginsRegistry
{
    /** @var array<class-string<Plugin>, Plugin> */
    private array $plugins = [];

    public function __construct(
        private readonly Logger $logger,
    ) {}

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

    /**
     * @return list<Outcome>
     */
    public function boot(Tempcord $tempcord): array
    {
        $outcomes = [];

        foreach ($this->plugins as $plugin) {
            $name = $this->nameOf($plugin);

            try {
                $plugin->boot($tempcord);

                $outcomes[] = Outcome::success('Plugin "' . $name . '" booted.');
            } catch (Throwable $throwable) {
                /*
                 * A plugin that cannot start is worth reporting loudly, but not
                 * worth taking the bot down for.
                 */
                $this->logger->error(
                    'Plugin "' . $name . '" failed to boot: ' . $throwable->getMessage(),
                    ['exception' => $throwable],
                );

                $outcomes[] = Outcome::error('Plugin "' . $name . '": ' . $throwable->getMessage());
            }
        }

        return $outcomes;
    }

    private function nameOf(Plugin $plugin): string
    {
        $class = $plugin::class;

        return substr($class, strrpos($class, '\\') + 1);
    }
}
