<?php

namespace Tempcord\Definitions;

use Tempcord\Enums\ComponentKind;
use Tempcord\Interfaces\Middleware;
use Tempcord\Runtime\CustomId;
use Tempest\Reflection\MethodReflector;

/**
 * A component handler and the custom id it answers, resolved once at discovery
 * time.
 */
final readonly class ComponentDefinition
{
    /**
     * @param list<Middleware|class-string<Middleware>> $middleware run around
     *        this handler, outermost first
     */
    public function __construct(
        public ComponentKind $kind,
        public CustomId $customId,
        public string $handler,
        public MethodReflector $method,
        public array $middleware = [],
    ) {}

    /**
     * How this handler is named in start-up output and in errors.
     */
    public function label(): string
    {
        return $this->kind->value . ' "' . $this->customId->pattern . '"';
    }
}
