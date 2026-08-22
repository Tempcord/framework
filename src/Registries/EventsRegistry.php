<?php

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Discord;
use Tempcord\Definitions\EventDefinition;
use Tempcord\Runtime\Outcome;
use Tempest\Container\Container;
use Tempest\Container\Singleton;

/**
 * Holds every compiled event listener and attaches it to the gateway.
 */
#[Singleton]
final class EventsRegistry
{
    /** @var array<string, list<EventDefinition>> */
    private array $events = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    public function add(EventDefinition $event): void
    {
        $this->events[$event->name][] = $event;
    }

    /**
     * @return list<Outcome>
     */
    public function listen(Discord $discord): array
    {
        $outcomes = [];

        foreach ($this->events as $name => $events) {
            foreach ($events as $event) {
                $discord->gateway->events->on(
                    $name,
                    fn(object $payload) => $event->method->invokeArgs(
                        $this->container->get($event->listener),
                        [$payload],
                    ),
                );

                $outcomes[] = Outcome::success('Added listener for: ' . $name);
            }
        }

        return $outcomes;
    }
}
