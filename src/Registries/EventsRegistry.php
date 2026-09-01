<?php

namespace Tempcord\Registries;

use CyberWolf\Discord\Discord;
use Tempcord\Definitions\EventDefinition;
use Tempcord\Runtime\EventDispatcher;
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

        /*
         * Resolved here rather than in the constructor: discovery builds this
         * registry while the container is still being assembled, before the
         * initializers that provide the logger have themselves been found.
         */
        $dispatcher = $this->container->get(EventDispatcher::class);

        foreach ($this->events as $name => $events) {
            foreach ($events as $event) {
                $discord->gateway->events->on(
                    $name,
                    static fn(object $payload) => $dispatcher->dispatch($event, $payload),
                );

                $outcomes[] = Outcome::success('Added listener for: ' . $name);
            }
        }

        return $outcomes;
    }
}
