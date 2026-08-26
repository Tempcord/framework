<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;

/**
 * Groups every subcommand its class declares under one more level of nesting.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class SubcommandGroup
{
    public function __construct(
        public string|BackedEnum $name,
        public string $description,
    ) {}
}
