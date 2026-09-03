<?php

namespace Tempcord\Runtime;

use Tempest\Log\Logger;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Definitions\HandlerDefinition;
use Tempest\Container\Container;
use Throwable;
use function React\Async\async;

/**
 * Invokes the method behind a handler, with the class it lives on resolved from
 * the container so a command may take constructor dependencies of its own.
 */
final readonly class CommandDispatcher
{
    public function __construct(
        private ArgumentResolver $arguments,
        private Container $container,
        private Logger $logger,
        private MiddlewarePipeline $middleware,
    ) {}

    public function dispatch(HandlerDefinition $handler, CommandInteraction $interaction): void
    {
        /*
         * Resolving options can hit the REST API, which awaits, so the whole
         * dispatch runs inside a fiber. A command that throws must not take the
         * bot down with it, so failures are logged and the gateway carries on.
         */
        async(function () use ($handler, $interaction): void {
            try {
                /*
                 * Arguments are resolved inside the chain rather than before
                 * it: resolving them can cost a REST call, and a command a
                 * middleware is about to refuse should not pay for one.
                 */
                $this->middleware->run(
                    $handler->middleware,
                    $interaction,
                    function (CommandInteraction $interaction) use ($handler): void {
                        $handler->method->invokeArgs(
                            $this->container->get($handler->method->getDeclaringClass()->getName()),
                            $this->arguments->resolve($handler, $interaction),
                        );
                    },
                );
            } catch (Throwable $throwable) {
                $this->logger->error(
                    'Command "' . $handler->path . '" failed: ' . $throwable->getMessage(),
                    ['exception' => $throwable],
                );
            }
        })();
    }
}
