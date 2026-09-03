<?php

namespace Tempcord\Runtime;

use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Interfaces\Middleware;
use Tempest\Container\Container;

/**
 * Wraps a handler in the middleware declared around it.
 *
 * The chain is folded from the inside out so that the first middleware listed
 * is the outermost one — it sees the interaction first and decides whether
 * anything after it happens at all.
 *
 * Each one is built at the moment it is reached rather than up front, so a
 * refusal costs nothing but the middleware that refused: the ones behind it are
 * never constructed, and neither are the handler's arguments.
 */
final readonly class MiddlewarePipeline
{
    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param list<Middleware|class-string<Middleware>> $middleware
     * @param callable(CommandInteraction): void $handler
     */
    public function run(array $middleware, CommandInteraction $interaction, callable $handler): void
    {
        $next = $handler;

        foreach (array_reverse($middleware) as $entry) {
            $inner = $next;

            $next = function (CommandInteraction $interaction) use ($entry, $inner): void {
                ($this->resolve($entry))($interaction, $inner);
            };
        }

        $next($interaction);
    }

    /**
     * @param Middleware|class-string<Middleware> $entry
     */
    private function resolve(Middleware|string $entry): Middleware
    {
        return $entry instanceof Middleware ? $entry : $this->container->get($entry);
    }
}
