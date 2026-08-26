<?php

namespace Tempcord\Tests\Doubles;

use Psr\Log\AbstractLogger;
use Stringable;
use Tempest\Log\Logger;

/**
 * Keeps every message logged so tests can assert on what was reported.
 *
 * Implements Tempest's Logger rather than only PSR's, because that is what the
 * container resolves and therefore what the framework asks for.
 */
final class RecordingLogger extends AbstractLogger implements Logger
{
    /** @var list<string> */
    public array $messages = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->messages[] = (string) $message;
    }
}
