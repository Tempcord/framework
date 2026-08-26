<?php

namespace Tempcord\Discoveries;

use Tempcord\Attributes\Command;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Registries\CommandsRegistry;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class CommandsDiscovery implements Discovery
{
    use IsDiscovery;

    /**
     * The compiler is injected rather than defaulted so it arrives carrying the
     * container's localization provider; a default would silently compile every
     * command without translations.
     */
    public function __construct(
        private readonly CommandsRegistry $commandRegistry,
        private readonly CommandCompiler $compiler,
    ) {}

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getAttributes(Command::class) as $attribute) {
            $this->discoveryItems->add($location, $this->compiler->compile($class, $attribute));
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $command) {
            $this->commandRegistry->add($command);
        }
    }
}
