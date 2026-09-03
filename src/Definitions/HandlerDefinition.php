<?php

namespace Tempcord\Definitions;

use Tempcord\Interfaces\Middleware;
use Tempest\Reflection\MethodReflector;

/**
 * One callable endpoint of a command, flattened out of the subcommand tree.
 *
 * The path is the dotted name Discord reports for the interaction — "ping",
 * "moderation.kick", "music.playlist.play" — and doubles as the event name the
 * command extension emits.
 */
final readonly class HandlerDefinition
{
    /**
     * @param array<string, OptionDefinition> $options keyed by option name
     * @param string $optionPath the prefix getOption() needs to reach this
     *                           handler's options, empty for an invokable command
     * @param list<Middleware|class-string<Middleware>> $middleware everything
     *        declared around this handler, flattened outermost first — the
     *        command's, then its group's, then the subcommand's own
     */
    public function __construct(
        public string $path,
        public MethodReflector $method,
        public array $options,
        public string $optionPath = '',
        public array $middleware = [],
    ) {}

    /**
     * Where in the interaction payload the given option will be found.
     */
    public function pathTo(OptionDefinition $option): string
    {
        return $this->optionPath === ''
            ? $option->name
            : $this->optionPath . '.' . $option->name;
    }
}
