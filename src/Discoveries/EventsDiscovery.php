<?php

namespace Tempcord\Discoveries;

use Tempcord\Attributes\Event;
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
    )
    {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getAttributes(Event::class) as $attribute) {
            $attribute->reflector = $class;
            $this->discoveryItems->add($location, $attribute);
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $event) {
            $this->eventsRegistry->add($event);
        }
    }
}
