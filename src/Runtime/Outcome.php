<?php

namespace Tempcord\Runtime;

/**
 * Something the framework did, or failed to do, while starting up.
 *
 * Registries report what happened rather than printing it, so the console
 * command owns presentation and tests can assert on results directly.
 */
final readonly class Outcome
{
    private function __construct(
        public OutcomeLevel $level,
        public string $message,
    ) {}

    public static function success(string $message): self
    {
        return new self(OutcomeLevel::Success, $message);
    }

    public static function warning(string $message): self
    {
        return new self(OutcomeLevel::Warning, $message);
    }

    public static function error(string $message): self
    {
        return new self(OutcomeLevel::Error, $message);
    }
}
