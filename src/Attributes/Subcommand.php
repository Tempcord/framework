<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares a public method as a subcommand of the command its class declares.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Subcommand
{
    public function __construct(
        public string|BackedEnum $name,
        public string $description,
    ) {}
}
