<?php

namespace Tempcord\Runtime;

use CyberWolf\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Definitions\ComponentDefinition;
use Tempest\Container\Container;
use Tempest\Log\Logger;
use Throwable;
use function React\Async\async;

/**
 * Invokes the method behind a component handler, with the class it lives on
 * resolved from the container so a handler may take dependencies of its own.
 */
final readonly class ComponentDispatcher
{
    public function __construct(
        private ComponentArgumentResolver $arguments,
        private Container $container,
        private Logger $logger,
    ) {}

    /**
     * @param array<string, string> $parameters
     */
    public function dispatch(
        ComponentDefinition $definition,
        InteractionCreate $interaction,
        array $parameters,
    ): void {
        /*
         * As with commands: resolving arguments may await, and a handler that
         * throws must not take the gateway down with it.
         */
        async(function () use ($definition, $interaction, $parameters): void {
            try {
                $definition->method->invokeArgs(
                    $this->container->get($definition->handler),
                    $this->arguments->resolve($definition, $interaction, $parameters),
                );
            } catch (Throwable $throwable) {
                $this->logger->error(
                    'Handler for ' . $definition->label() . ' failed: ' . $throwable->getMessage(),
                    ['exception' => $throwable],
                );
            }
        })();
    }
}
