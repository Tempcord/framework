<?php

declare(strict_types=1);

namespace Tempcord\Plugins;

use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;
use function Tempest\get;

/**
 * Discovers classes marked with #[Plugin] attribute.
 *
 * Plugins are automatically discovered from all Composer packages
 * that require Tempcord, following Tempest's discovery conventions.
 */
final class Discovery implements \Tempest\Discovery\Discovery
{
    use IsDiscovery;

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        if ($class->implements(Plugin::class)) {
            $this->discoveryItems->add($location, $class);
        }
    }

    public function apply(): void
    {
        $registry = get(Registry::class);
        foreach ($this->discoveryItems as $plugin) {
            $registry->register(get($plugin->getName()));
        }
    }
}
