<?php

namespace Tempcord\Middleware;

use Tempcord\CommandInteraction;
use Tempest\Container\Container;

class MiddlewarePipeline
{
    /** @var array<CommandMiddleware> */
    private array $middleware = [];

    public function __construct(
        private readonly Container $container
    )
    {
    }

    public function pipe(CommandMiddleware $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * @param array<class-string<CommandMiddleware>> $middlewareClasses
     */
    public function through(array $middlewareClasses): self
    {
        foreach ($middlewareClasses as $middlewareClass) {
            $this->middleware[] = $this->container->get($middlewareClass);
        }
        return $this;
    }

    public function process(CommandInteraction $interaction, callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            static fn(callable $next, CommandMiddleware $middleware) =>
                static fn(CommandInteraction $i) => $middleware->handle($i, $next),
            $destination
        );

        return $pipeline($interaction);
    }
}
