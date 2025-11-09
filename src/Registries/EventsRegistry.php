<?php

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Discord;
use Tempcord\Attributes\Event;
use Tempcord\Tempcord;
use Tempest\Console\Console;
use Tempest\Container\Singleton;
use function Tempest\get;

#[Singleton]
final class EventsRegistry
{
    /** @var array<array<string, callable>> */
    private array $eventListeners = [];



    public function add(Event $event): void
    {
        $this->eventListeners[$event->name][] = static fn(object $eventObject) => $event->handler->invokeArgs(
            object: get($event->reflector->getName()),
            args: [$eventObject]
        );
    }

    public function listen(Console $console): void
    {
        $console->header('Starting Events');
        $tempcord = get(Tempcord::class);

        foreach ($this->eventListeners as $event => $eventListeners) {
            foreach ($eventListeners as $eventListener) {
                $tempcord->discord->gateway->events->on($event, $eventListener);
                $console->success('Added listener for: ' . $event);
            }
        }
    }
}