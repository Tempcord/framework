<?php

namespace Tempcord\Runtime\Command;

use Tempest\Log\Logger;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Compile\Command\Definitions\HandlerDefinition;
use Tempest\Container\Container;
use Tempcord\Runtime\Concerns\ContainsFailures;
use Tempcord\Runtime\Middleware\MiddlewarePipeline;

/**
 * Invokes the method behind a handler, with the class it lives on resolved from
 * the container so a command may take constructor dependencies of its own.
 */
final readonly class CommandDispatcher
{
    use ContainsFailures;

    public function __construct(
        private ArgumentResolver $arguments,
        private Container $container,
        private Logger $logger,
        private MiddlewarePipeline $middleware,
    ) {}

    public function dispatch(HandlerDefinition $handler, CommandInteraction $interaction): void
    {
        // Resolving options can hit the REST API, which awaits.
        $this->contained('Command "' . $handler->path . '"', function () use ($handler, $interaction): void {
            /*
             * Arguments are resolved inside the chain rather than before it:
             * resolving them can cost a REST call, and a command a middleware
             * is about to refuse should not pay for one.
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
        });
    }
}
