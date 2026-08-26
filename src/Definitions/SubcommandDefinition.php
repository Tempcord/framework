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
     * @param array<string, string> $nameLocalizations keyed by Discord locale
     * @param array<string, string> $descriptionLocalizations keyed by Discord locale
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $options,
        public MethodReflector $method,
        public array $nameLocalizations = [],
        public array $descriptionLocalizations = [],
    ) {}
}
