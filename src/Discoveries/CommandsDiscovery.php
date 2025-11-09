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
        private readonly CommandsRegistry $registry
    )
    {

    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getAttributes(Command::class) as $command) {
            $command->reflector = $class;
            $this->discoveryItems->add($location, $command);
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $command) {
            $this->registry->bucket->add($command);
        }
    }
}
