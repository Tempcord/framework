<?php

namespace Tempcord\Discoveries;

use Tempcord\Attributes\Scheduled;
use Tempcord\Compiler\ScheduledTaskCompiler;
use Tempcord\Registries\ScheduledTasksRegistry;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class ScheduledTasksDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly ScheduledTasksRegistry $registry,
        private readonly ScheduledTaskCompiler $compiler = new ScheduledTaskCompiler(),
    ) {}

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getAttributes(Scheduled::class) as $attribute) {
            $this->discoveryItems->add($location, $this->compiler->compile($class, $attribute));
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $task) {
            $this->registry->add($task);
        }
    }
}
