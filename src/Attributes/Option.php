<?php

namespace Tempcord\Attributes;

use Attribute;
use Tempcord\Interfaces\Autocomplete;

/**
 * Declares a method parameter as a user-supplied command option.
 *
 * The option's Discord type comes from the parameter's PHP type and whether it
 * is required comes from whether the parameter has a default, both resolved by
 * the CommandCompiler.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Option
{
    /**
     * @param string|null $name defaults to the parameter's own name
     */
    public function __construct(
        public string $description,
        public ?string $name = null,
        public ?Autocomplete $autocomplete = null,
    ) {}
}
