<?php

namespace Tempcord\Runtime\Event;

use Tempcord\Compile\Event\EventDefinition;
use Tempcord\Runtime\Concerns\ContainsFailures;
use Tempest\Container\Container;
use Tempest\Log\Logger;

/**
 * Invokes a gateway listener, with the class it lives on resolved from the
 * container so a listener may take dependencies of its own.
 */
final readonly class EventDispatcher
{
    use ContainsFailures;

    public function __construct(
        private Container $container,
        private Logger $logger,
    ) {}

    public function dispatch(EventDefinition $event, object $payload): void
    {
        $this->contained('Listener for ' . $event->name, function () use ($event, $payload): void {
            $event->method->invokeArgs($this->container->get($event->listener), [$payload]);
        });
    }
}
