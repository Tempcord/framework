<?php

namespace Tempcord\Runtime;

use Tempcord\Plugins\Plugin;
use Tempcord\Tempcord;
use Tempest\Log\Logger;
use Throwable;

/**
 * Boots each plugin, reporting on it and containing anything it throws.
 */
final readonly class PluginBooter
{
    public function __construct(
        private Logger $logger,
    ) {}

    /**
     * @param list<Plugin> $plugins
     *
     * @return list<Outcome>
     */
    public function bootAll(array $plugins, Tempcord $tempcord): array
    {
        $outcomes = [];

        foreach ($plugins as $plugin) {
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
