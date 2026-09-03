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
    /**
     * @param list<\Tempcord\Interfaces\Middleware|class-string<\Tempcord\Interfaces\Middleware>> $middleware
     *        run after whatever the command and the group around it declare
     */
    public function __construct(
        public string|BackedEnum $name,
        public string $description,
        public array $middleware = [],
    ) {}
}
