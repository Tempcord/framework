<?php

declare(strict_types=1);

namespace Tempcord\Attributes\Commands;

use Attribute;
use Tempcord\Support\Traits\HasAttributes;
use Tempest\Reflection\ClassReflector;
use function Tempest\Support\str;

/**
 * Marks a class as an autocomplete handler for command options.
 *
 * Autocomplete classes must implement __invoke(string $value, InteractionCreate $interaction): array
 *
 * Example:
 * ```php
 * #[Autocomplete]
 * class UserAutocomplete {
 *     public function __invoke(string $value, InteractionCreate $interaction): array {
 *         return ['user1', 'user2', 'user3'];
 *     }
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Autocomplete
{
    use HasAttributes;

    public ClassReflector $reflector;

    /**
     * Autocomplete handler name
     *
     * If none is provided from constructor - name would be created from the class name,
     * removing "Autocomplete" from the end of the class name.
     * Additionally, it will be snake_case.
     */
    public string $name {
        get {
            return $this->rememberAttribute('name', fn() => str($this->reflector->getShortName())
                ->replaceEnd('Autocomplete', '')
                ->snake('_')
                ->lower()
                ->toString());
        }
    }

    /**
     * @param string|null $name Unique identifier for this autocomplete handler
     */
    public function __construct(
        ?string $name = null,
    ) {
        $this->setAttribute('name', $name);
    }

    public function withReflector(ClassReflector $reflector): self
    {
        $this->reflector = $reflector;
        return $this;
    }
}
