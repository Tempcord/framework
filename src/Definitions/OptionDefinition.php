<?php

namespace Tempcord\Definitions;

use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Tempcord\Interfaces\Autocomplete;
use Tempest\Reflection\ParameterReflector;

/**
 * A single user-supplied argument to a command or subcommand, resolved from a
 * #[Option] attribute and the parameter it sits on.
 */
final readonly class OptionDefinition
{
    public function __construct(
        public string $name,
        public string $description,
        public ApplicationCommandOptionType $type,
        public bool $isRequired,
        public ?Autocomplete $autocomplete,
        public ParameterReflector $parameter,
    ) {}

    public function hasAutocomplete(): bool
    {
        return $this->autocomplete !== null;
    }
}
