<?php

namespace Tempcord\Discoveries;

use Tempcord\Attributes\Command;
use Tempcord\Registries\CommandsRegistry;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class CommandsDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly CommandsRegistry $commandRegistry,
    )
    {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getAttributes(Command::class) as $attribute) {
            $attribute->reflector = $class;
            $this->discoveryItems->add($location, $attribute);
        }
    }

    /**
     * @mago-expect best-practices/no-empty-loop
     */
    public function apply(): void
    {
        foreach ($this->discoveryItems as $command) {
            $this->commandRegistry->add($command);
        }
    }
}
