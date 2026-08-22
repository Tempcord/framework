<?php

namespace Tempcord\Tests\Doubles;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Keeps every message logged so tests can assert on what was reported.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $messages = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->messages[] = (string) $message;
    }
}
