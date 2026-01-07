<?php

declare(strict_types=1);

namespace Tempcord\Attributes\Components;

use Attribute;
use Tempcord\Contract\CanBeHandled;
use Tempest\Reflection\MethodReflector;

#[Attribute(Attribute::TARGET_METHOD)]
final class Button implements CanBeHandled
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
        // Exact match
        if ($this->customId === $customId) {
            return true;
        }

        // Wildcard match (e.g., "delete_*" matches "delete_123")
        if (str_contains($this->customId, '*')) {
            $pattern = '/^' . str_replace('\*', '.*', preg_quote($this->customId, '/')) . '$/';
            return (bool) preg_match($pattern, $customId);
        }

        return false;
    }

    /**
     * Extract wildcard values from custom_id
     * e.g., "delete_*" with "delete_123" returns ["123"]
     */
    public function extractParams(string $customId): array
    {
        if (!str_contains($this->customId, '*')) {
            return [];
        }

        $pattern = '/^' . str_replace('\*', '(.*)', preg_quote($this->customId, '/')) . '$/';
        if (preg_match($pattern, $customId, $matches)) {
            array_shift($matches); // Remove full match
            return $matches;
        }

        return [];
    }

    public \Tempcord\Support\Commands\CommandHandler $handler {
        get {
            return $this->handler;
        }
    }
}
