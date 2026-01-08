<?php

namespace Tempcord\Attributes\Prompts;

use Attribute;
use Closure;
use Tempcord\Support\Traits\HasAttributes;
use Tempest\Reflection\ParameterReflector;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class PromptOption
{
    use HasAttributes;

    public ParameterReflector $reflector;

    public string $name {
        get {
            if ($this->hasAttribute('name')) {
                return $this->getAttribute('name');
            }

            return str($this->reflector->getName())->toString();
        }
    }

    public bool $isRequired {
        get {
            return !$this->reflector->isOptional();
        }
    }

    public bool $isFlag {
        get {
            return $this->getAttribute('isFlag', false);
        }
    }

    public function __construct(
        public string $description,
        ?string       $name = null,
        public mixed  $autocomplete = null,
        bool          $isFlag = false,
    )
    {
        $this->setAttribute('name', $name);
        $this->setAttribute('isFlag', $isFlag);
        $this->validateAutocomplete($autocomplete);
    }

    /**
     * Validate that autocomplete is one of the supported types.
     *
     * @throws \InvalidArgumentException
     */
    private function validateAutocomplete(mixed $autocomplete): void
    {
        if ($autocomplete === null) {
            return;
        }

        $valid = $autocomplete instanceof Closure
            || is_array($autocomplete); // Array of values

        if (!$valid) {
            throw new \InvalidArgumentException(
                'Autocomplete must be a Closure or array of values'
            );
        }
    }

    public function withReflector(ParameterReflector $reflector): PromptOption
    {
        $this->reflector = $reflector;
        return $this;
    }

    /**
     * Map string value from input to the parameter type
     */
    public function mapValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!$this->reflector->getReflection()->hasType()) {
            return $value;
        }

        $type = $this->reflector->getType();

        return match ($type->getName()) {
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
