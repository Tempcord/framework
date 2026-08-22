<?php

namespace Tempcord\Attributes;

use Attribute;
use Ragnarok\Fenrir\Enums\ChannelType;
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
     * @param string $description shown beneath the option in Discord's picker
     * @param string|null $name defaults to the parameter's own name
     * @param Autocomplete|null $autocomplete suggests values as the user types;
     *        mutually exclusive with choices
     * @param array<string, string|int|float>|list<string|int|float> $choices
     *        the only values Discord will accept. A map uses its keys as the
     *        labels users see; a list shows each value as its own label.
     *        Mutually exclusive with autocomplete.
     * @param int|float|null $minValue smallest accepted number
     * @param int|float|null $maxValue largest accepted number
     * @param int|null $minLength shortest accepted string
     * @param int|null $maxLength longest accepted string
     * @param list<ChannelType> $channelTypes restricts which channels may be
     *        picked, for a Channel option
     */
    public function __construct(
        public string $description,
        public ?string $name = null,
        public ?Autocomplete $autocomplete = null,
        public array $choices = [],
        public int|float|null $minValue = null,
        public int|float|null $maxValue = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public array $channelTypes = [],
    ) {}
}
