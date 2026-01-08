<?php

namespace Tempcord\Support\Prompts;

final readonly class ParsedPrompt
{
    /**
     * @param string $name Prompt name
     * @param array<string, mixed> $arguments Parsed arguments (named and positional)
     */
    public function __construct(
        public string $name,
        public array  $arguments = [],
    )
    {
    }
}
