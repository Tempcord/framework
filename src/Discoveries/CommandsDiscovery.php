<?php

namespace Tempcord\Discoveries;

use Tempcord\Attributes\Commands\Command;
use Tempcord\Registries\CommandsRegistry;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;
use function Tempest\get;

final class CommandsDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly CommandsRegistry $registry,
    )
    {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        /** @var Command $command */
        foreach ($class->getAttributes(Command::class) as $command) {
            $this->discoveryItems->add($location, $command->withReflector($class));
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $command) {
            $this->registry->bucket->add($command);
        }
    }
}
