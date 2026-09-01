<?php

namespace Tempcord\Runtime;

use Tempcord\Definitions\EventDefinition;
use Tempest\Container\Container;
use Tempest\Log\Logger;
use Throwable;
use function React\Async\async;

/**
 * Invokes a gateway listener, with the class it lives on resolved from the
 * container so a listener may take dependencies of its own.
 */
final readonly class EventDispatcher
{
    public function __construct(
        private Container $container,
        private Logger $logger,
    ) {}

    public function dispatch(EventDefinition $event, object $payload): void
    {
        /*
         * In a fiber, so a listener may await the REST API, and inside a catch,
         * so one that throws is logged instead of travelling up into the
         * gateway's payload handler and taking the connection down.
         */
        async(function () use ($event, $payload): void {
            try {
                $event->method->invokeArgs($this->container->get($event->listener), [$payload]);
            } catch (Throwable $throwable) {
                $this->logger->error(
                    'Listener for ' . $event->name . ' failed: ' . $throwable->getMessage(),
                    ['exception' => $throwable],
                );
            }
        })();
    }
}
