<?php

declare(strict_types=1);

namespace Tempcord\Registries;

use Tempcord\Attributes\Commands\Autocomplete;
use Tempest\Container\Singleton;

/**
 * Registry for autocomplete handlers.
 *
 * Stores discovered autocomplete classes and provides lookup by name or class reference.
 */
#[Singleton]
class AutocompleteRegistry
{
    /** @var array<string, Autocomplete> Autocomplete handlers indexed by name */
    private array $handlers = [];

    /** @var array<class-string, Autocomplete> Autocomplete handlers indexed by class name */
    private array $handlersByClass = [];

    public function register(Autocomplete $autocomplete): void
    {
        $name = $autocomplete->name;
        $className = $autocomplete->reflector->getName();

        $this->handlers[$name] = $autocomplete;
        $this->handlersByClass[$className] = $autocomplete;
    }

    /**
     * Get autocomplete handler by name
     */
    public function getByName(string $name): ?Autocomplete
    {
        return $this->handlers[$name] ?? null;
    }

    /**
     * Get autocomplete handler by class name
     */
    public function getByClass(string $className): ?Autocomplete
    {
        return $this->handlersByClass[$className] ?? null;
    }

    /**
     * Get all registered handlers
     *
     * @return array<string, Autocomplete>
     */
    public function getAll(): array
    {
        return $this->handlers;
    }
}
