<?php

namespace Tempcord\Tools;

final readonly class Parameter
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $default,
        public string $summary,
    ) {}

    public function isRequired(): bool
    {
        return $this->default === null;
    }
}
