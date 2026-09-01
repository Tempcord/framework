<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Button;

/**
 * Several buttons on one class, keyed by what travels in the custom id.
 */
final class TournamentButtons
{
    /** @var list<array{string, string|int}> */
    public static array $calls = [];

    #[Button(id: 'tournament.accept.{team}')]
    public function accept(string $team): void
    {
        self::$calls[] = ['accept', $team];
    }

    #[Button(id: 'tournament.reject.{team}')]
    #[Button(id: 'tournament.drop.{team}')]
    public function reject(int $team): void
    {
        self::$calls[] = ['reject', $team];
    }
}
