<?php

namespace Tempcord\Discoveries;

use Tempcord\Compiler\ComponentCompiler;
use Tempcord\Registries\ComponentsRegistry;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class ComponentsDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly ComponentsRegistry $componentsRegistry,
        private readonly ComponentCompiler $compiler = new ComponentCompiler(),
    ) {}

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($this->compiler->compile($class) as $component) {
            $this->discoveryItems->add($location, $component);
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $component) {
            $this->componentsRegistry->add($component);
        }
    }
}
