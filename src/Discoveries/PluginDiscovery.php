<?php

declare(strict_types=1);

namespace Tempcord\Discoveries;

use Tempcord\Attributes\TempcordPlugin;
use Tempcord\Plugins\PluginRegistry;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

/**
 * Discovers classes marked with #[TempcordPlugin] attribute.
 *
 * Plugins are automatically discovered from all Composer packages
 * that require Tempcord, following Tempest's discovery conventions.
 */
final class PluginDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly PluginRegistry $registry,
    ) {}

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getAttributes(TempcordPlugin::class) as $attribute) {
            /** @var TempcordPlugin $pluginAttribute */
            $pluginAttribute = $attribute->withReflector($class);

            if ($pluginAttribute->enabled && $pluginAttribute->isValidPlugin()) {
                $this->discoveryItems->add($location, $pluginAttribute);
            }
        }
    }

    public function apply(): void
    {
        // Collect all plugins with their priorities
        $plugins = [];

        foreach ($this->discoveryItems as $pluginAttribute) {
            /** @var TempcordPlugin $pluginAttribute */
            $plugins[] = $pluginAttribute;
        }

        // Sort by priority (lower = earlier)
        usort($plugins, fn(TempcordPlugin $a, TempcordPlugin $b) => $a->priority <=> $b->priority);

        // Register plugins in priority order
        foreach ($plugins as $pluginAttribute) {
            $this->registry->register($pluginAttribute);
        }
    }
}
