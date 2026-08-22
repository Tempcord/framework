<?php

namespace Tempcord\Definitions;

use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Enums\ChannelType;
use Tempcord\Interfaces\Autocomplete;
use Tempest\Reflection\ParameterReflector;

/**
 * A single user-supplied argument to a command or subcommand, resolved from a
 * #[Option] attribute and the parameter it sits on.
 */
final readonly class OptionDefinition
{
    /**
     * @param array<string, string|int|float> $choices keyed by the label users see
     * @param list<ChannelType> $channelTypes
     * @param array<string, string> $nameLocalizations keyed by Discord locale
     * @param array<string, string> $descriptionLocalizations keyed by Discord locale
     */
    public function __construct(
        public string $name,
        public string $description,
        public ApplicationCommandOptionType $type,
        public bool $isRequired,
        public ?Autocomplete $autocomplete,
        public ParameterReflector $parameter,
        public array $choices = [],
        public int|float|null $minValue = null,
        public int|float|null $maxValue = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public array $channelTypes = [],
        public array $nameLocalizations = [],
        public array $descriptionLocalizations = [],
    ) {}

    public function hasAutocomplete(): bool
    {
        return $this->autocomplete !== null;
    }
}
