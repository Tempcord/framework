<?php

namespace Tempcord\Definitions;

/**
 * A named grouping of subcommands, which Discord renders as one more level of
 * nesting under the command itself.
 */
final readonly class SubcommandGroupDefinition
{
    /**
     * @param array<string, SubcommandDefinition> $subcommands keyed by subcommand name
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $subcommands,
    ) {}
}
