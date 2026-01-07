<?php

declare(strict_types=1);

namespace Tempcord\Attributes\Components;

use Attribute;
use Tempcord\Contract\CanBeHandled;
use Tempest\Reflection\MethodReflector;

#[Attribute(Attribute::TARGET_METHOD)]
final class Modal implements CanBeHandled
{
    public ?MethodReflector $reflector = null;

    /**
     * @param string $customId The custom_id to match (supports wildcards with *)
     * @param array<class-string> $middleware Middleware to apply
     */
    public function __construct(
        public readonly string $customId,
        public readonly array $middleware = [],
    ) {}

    public function setReflector(MethodReflector $reflector): void
    {
        $this->reflector = $reflector;
    }

    /**
     * Check if this handler matches the given custom_id
     */
    public function matches(string $customId): bool
    {
        if ($this->customId === $customId) {
            return true;
        }

        if (str_contains($this->customId, '*')) {
            $pattern = '/^' . str_replace('\*', '.*', preg_quote($this->customId, '/')) . '$/';
            return (bool) preg_match($pattern, $customId);
        }

        return false;
    }

    /**
     * Extract wildcard values from custom_id
     */
    public function extractParams(string $customId): array
    {
        if (!str_contains($this->customId, '*')) {
            return [];
        }

        $pattern = '/^' . str_replace('\*', '(.*)', preg_quote($this->customId, '/')) . '$/';
        if (preg_match($pattern, $customId, $matches)) {
            array_shift($matches);
            return $matches;
        }

        return [];
    }
}
