<?php

declare(strict_types=1);

namespace Tempcord\Plugins;

use Ragnarok\Fenrir\Discord;
use ReflectionClass;
use Tempest\Container\Container;

/**
 * Base class for Tempcord plugins with sensible defaults.
 *
 * Name, version, and description are automatically read from the plugin's
 * composer.json file. Override these methods if you need custom behavior.
 */
abstract class AbstractPlugin implements Plugin
{
    private ?array $composerData = null;

    /**
     * Get the plugin name from composer.json.
     */
    public function name(): string
    {
        return $this->getComposerData()['name'] ?? static::class;
    }

    /**
     * Get the plugin version from composer.json.
     */
    public function version(): string
    {
        return $this->getComposerData()['version'] ?? 'dev';
    }

    /**
     * Get the plugin description from composer.json.
     */
    public function description(): string
    {
        return $this->getComposerData()['description'] ?? '';
    }

    public function register(Container $container): void
    {
        // Override to register services
    }

    public function boot(Discord $discord): void
    {
        // Override to set up Discord-specific features
    }

    public function middleware(): array
    {
        return [];
    }

    public function discoveryNamespaces(): array
    {
        return [];
    }

    /**
     * Get the parsed composer.json data for this plugin.
     *
     * Searches for composer.json starting from the plugin class directory
     * and walking up the directory tree.
     */
    protected function getComposerData(): array
    {
        if ($this->composerData !== null) {
            return $this->composerData;
        }

        $this->composerData = [];

        try {
            $reflection = new ReflectionClass(static::class);
            $classFile = $reflection->getFileName();

            if ($classFile === false) {
                return $this->composerData;
            }

            $directory = dirname($classFile);

            // Walk up directory tree looking for composer.json
            while ($directory !== dirname($directory)) {
                $composerFile = $directory . '/composer.json';

                if (file_exists($composerFile)) {
                    $content = file_get_contents($composerFile);
                    if ($content !== false) {
                        $data = json_decode($content, true);
                        if (is_array($data)) {
                            $this->composerData = $data;
                            break;
                        }
                    }
                }

                $directory = dirname($directory);
            }
        } catch (\Throwable) {
            // Silently fail, return empty array
        }

        return $this->composerData;
    }
}
