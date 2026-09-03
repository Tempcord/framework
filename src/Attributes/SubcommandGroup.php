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
    /**
     * @param list<\Tempcord\Interfaces\Middleware|class-string<\Tempcord\Interfaces\Middleware>> $middleware
     *        run after whatever the command declares, before the subcommand's own
     */
    public function __construct(
        public string|BackedEnum $name,
        public string $description,
        public array $middleware = [],
    ) {}
}
