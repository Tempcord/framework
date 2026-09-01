<?php

namespace Tempcord\Runtime;

use Tempcord\Definitions\ComponentDefinition;

/**
 * The handler an incoming custom id resolved to, and the placeholder values
 * read out of that id along the way.
 */
final readonly class ComponentMatch
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public ComponentDefinition $definition,
        public array $parameters,
    ) {}
}
