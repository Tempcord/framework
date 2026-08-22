<?php

namespace Tempcord\Definitions;

use Tempest\Reflection\MethodReflector;

/**
 * One method exposed as a subcommand, together with the options it accepts.
 */
final readonly class SubcommandDefinition
{
    /**
     * @param array<string, OptionDefinition> $options keyed by option name
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $options,
        public MethodReflector $method,
    ) {}
}
