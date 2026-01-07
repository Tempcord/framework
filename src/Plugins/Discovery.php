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

    public function __construct()
    {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getAttributes(Plugin::class) as $attribute) {
            /** @var Plugin $pluginAttribute */
            $pluginAttribute = $attribute->withReflector($class);

            if ($pluginAttribute->enabled && $pluginAttribute->isValidPlugin()) {
                $this->discoveryItems->add($location, $pluginAttribute);
            }
        }
    }

    public function apply(): void
    {
        $registry = get(Registry::class);
        foreach ($this->discoveryItems as $pluginAttribute) {
            $registry->register($pluginAttribute);
        }
    }
}
