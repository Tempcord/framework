<?php

namespace Tempcord\Compiler;

use LogicException;
use Tempcord\Interfaces\Middleware;

/**
 * Middleware as an attribute declared it, checked before anything is built out
 * of it.
 *
 * The check belongs at discovery because discovery is start-up: a class named
 * as a guard that turns out not to be one should stop the bot booting, not
 * surface the first time somebody uses the thing it was meant to guard — which,
 * for a guard, is the worst possible moment to find out.
 *
 * Shared by both compilers so a button and a subcommand are held to the same
 * rule and refused in the same words.
 */
final readonly class DeclaredMiddleware
{
    /**
     * @param array<mixed> $declared
     * @param string $where what is being compiled, named for the error
     *
     * @return list<Middleware|class-string<Middleware>>
     */
    public static function checked(array $declared, string $where): array
    {
        $middleware = [];

        foreach ($declared as $entry) {
            if ($entry instanceof Middleware) {
                $middleware[] = $entry;
                continue;
            }

            if (is_string($entry) && is_subclass_of($entry, Middleware::class)) {
                $middleware[] = $entry;
                continue;
            }

            throw new LogicException(
                $where . ' declares middleware ['
                . (is_string($entry) ? $entry : get_debug_type($entry))
                . '], which does not implement ' . Middleware::class,
            );
        }

        return $middleware;
    }
}
