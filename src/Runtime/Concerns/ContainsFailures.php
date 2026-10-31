<?php

namespace Tempcord\Runtime\Concerns;

use Closure;
use Throwable;
use function React\Async\async;

/**
 * Runs code from the user's side of the bot — a handler, a listener, a source
 * of suggestions — so it can neither stall nor break the gateway.
 *
 * In a fiber, so it may await the REST API, and inside a catch, so one that
 * throws is logged instead of travelling up into the gateway's payload handler
 * and taking the connection down with it.
 *
 * The class using this must have a `Tempest\Log\Logger` in `$this->logger`.
 */
trait ContainsFailures
{
    /**
     * @param string $what names what is running, for the log: 'Command "ping"'
     */
    private function contained(string $what, Closure $work): void
    {
        async(function () use ($what, $work): void {
            try {
                $work();
            } catch (Throwable $throwable) {
                $this->logger->error(
                    $what . ' failed: ' . $throwable->getMessage(),
                    ['exception' => $throwable],
                );
            }
        })();
    }
}
