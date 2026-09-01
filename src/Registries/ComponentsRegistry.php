<?php

namespace Tempcord\Registries;

use LogicException;
use Tempcord\Definitions\ComponentDefinition;
use Tempcord\Enums\ComponentKind;
use Tempcord\Runtime\ComponentMatch;
use Tempest\Container\Singleton;

/**
 * Holds every compiled component handler, and resolves an incoming custom id
 * back to one of them.
 *
 * Literal ids are kept apart from patterned ones so the common case is a hash
 * lookup and only the rest are walked.
 */
#[Singleton]
final class ComponentsRegistry
{
    /** @var array<string, array<string, ComponentDefinition>> */
    private array $literals = [];

    /** @var array<string, list<ComponentDefinition>> */
    private array $patterns = [];

    public function add(ComponentDefinition $definition): void
    {
        $kind = $definition->kind->value;
        $pattern = $definition->customId->pattern;

        foreach ([...($this->literals[$kind] ?? []), ...($this->patterns[$kind] ?? [])] as $existing) {
            if ($existing->customId->pattern === $pattern) {
                throw new LogicException(
                    'Two handlers answer the same ' . $definition->label() . ': '
                    . $existing->handler . ' and ' . $definition->handler,
                );
            }
        }

        if ($definition->customId->isLiteral()) {
            $this->literals[$kind][$pattern] = $definition;

            return;
        }

        $this->patterns[$kind][] = $definition;
    }

    /**
     * @return list<ComponentDefinition>
     */
    public function all(): array
    {
        $definitions = [];

        foreach ($this->literals as $byPattern) {
            foreach ($byPattern as $definition) {
                $definitions[] = $definition;
            }
        }

        foreach ($this->patterns as $forKind) {
            foreach ($forKind as $definition) {
                $definitions[] = $definition;
            }
        }

        return $definitions;
    }

    /**
     * The handler for this id, or null when nothing answers it.
     *
     * A literal id always wins over a pattern that would also match it, so an
     * exception to a family of ids can be declared without ordering games.
     */
    public function match(ComponentKind $kind, string $customId): ?ComponentMatch
    {
        $definition = $this->literals[$kind->value][$customId] ?? null;

        if ($definition !== null) {
            return new ComponentMatch($definition, []);
        }

        foreach ($this->patterns[$kind->value] ?? [] as $candidate) {
            $parameters = $candidate->customId->match($customId);

            if ($parameters !== null) {
                return new ComponentMatch($candidate, $parameters);
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->all());
    }
}
