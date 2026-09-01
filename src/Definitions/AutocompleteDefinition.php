<?php

namespace Tempcord\Definitions;

use Tempcord\Interfaces\Autocomplete;
use Tempest\Reflection\MethodReflector;

/**
 * Where an option's suggestions come from.
 *
 * There are three ways to supply them and they are resolved differently — an
 * object is used as it stands, a class name is built by the container, and a
 * method is called on the command it was declared on. Which one it is is
 * settled here, at discovery time, so the runtime only has to act on it.
 */
final readonly class AutocompleteDefinition
{
    /**
     * @param class-string<Autocomplete>|null $className
     */
    private function __construct(
        public ?Autocomplete $instance = null,
        public ?string $className = null,
        public ?MethodReflector $method = null,
    ) {}

    /**
     * An autocomplete built inside the attribute itself. It can take no
     * dependencies, since the container is not involved in reading attributes.
     */
    public static function fromInstance(Autocomplete $autocomplete): self
    {
        return new self(instance: $autocomplete);
    }

    /**
     * An autocomplete named by class and built by the container, so it may take
     * whatever it needs.
     *
     * @param class-string<Autocomplete> $className
     */
    public static function fromClass(string $className): self
    {
        return new self(className: $className);
    }

    /**
     * A method on the command itself, carrying a #[Autocomplete] attribute.
     */
    public static function fromMethod(MethodReflector $method): self
    {
        return new self(method: $method);
    }

    /**
     * How this source is named in an error, so a failing autocomplete can be
     * traced back to what wrote it.
     */
    public function label(): string
    {
        if ($this->method !== null) {
            return $this->method->getDeclaringClass()->getName() . '::' . $this->method->getName() . '()';
        }

        return $this->className ?? $this->instance::class;
    }
}
