<?php

namespace Tempcord\Discoveries;

use Tempcord\Plugins\Plugin;
use Tempcord\Registries\PluginsRegistry;
use Tempest\Container\Container;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class PluginsDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly Container $container,
        private readonly PluginsRegistry $plugins,
    ) {}

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        $reflection = $class->getReflection();

        if (!$class->implements(Plugin::class) || $reflection->isAbstract() || $reflection->isInterface()) {
            return;
        }

        $this->discoveryItems->add($location, $class->getName());
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $className) {
            /** @var Plugin $plugin */
            $plugin = $this->container->get($className);

            $this->plugins->add($plugin);
        }
    }
}
