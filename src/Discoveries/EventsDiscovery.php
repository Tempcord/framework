<?php

namespace Tempcord\Discoveries;

use Tempcord\Attributes\Event;
use Tempcord\Compiler\EventCompiler;
use Tempcord\Registries\EventsRegistry;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class EventsDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly EventsRegistry $eventsRegistry,
        private readonly EventCompiler $compiler = new EventCompiler(),
    ) {}

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getAttributes(Event::class) as $attribute) {
            $this->discoveryItems->add($location, $this->compiler->compile($class, $attribute));
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $event) {
            $this->eventsRegistry->add($event);
        }
    }
}
