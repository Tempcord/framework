<?php

namespace Tempcord\Tools;

/**
 * One documented thing: an attribute, an enum, an interface or a class.
 */
final readonly class Symbol
{
    /**
     * @param list<Parameter> $parameters
     * @param list<array{name: string, value: string, note: string}> $cases
     * @param list<array{signature: string, summary: string}> $methods
     */
    public function __construct(
        public string $name,
        public string $fqcn,
        public string $kind,
        public string $summary,
        public string $slug,
        public array $parameters = [],
        public array $cases = [],
        public array $methods = [],
        public ?string $target = null,
    ) {}
}
