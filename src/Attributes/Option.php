<?php

namespace Tempcord\Attributes;

use Attribute;
use Tempcord\Discord\Enums\ChannelType;
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
     * @param Autocomplete|class-string<Autocomplete>|null $autocomplete suggests
     *        values as the user types; mutually exclusive with choices. Given a
     *        class name it is built by the container, so it may take
     *        dependencies; given an object it is used as it stands. Suggestions
     *        that belong to one command are usually better written as a method
     *        carrying #[Autocomplete].
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
        public Autocomplete|string|null $autocomplete = null,
        public array $choices = [],
        public int|float|null $minValue = null,
        public int|float|null $maxValue = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public array $channelTypes = [],
    ) {}
}
