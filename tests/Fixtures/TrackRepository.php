<?php

namespace Tempcord\Tests\Fixtures;

/**
 * Stands in for whatever an autocomplete would really read from — a database,
 * an API — so a test can prove the container reached it.
 */
final class TrackRepository
{
    /** @return list<string> */
    public function matching(string $typed): array
    {
        return array_values(array_filter(
            ['Kalush', 'Okean Elzy', 'Boombox'],
            static fn(string $track) => $typed === '' || str_contains(strtolower($track), strtolower($typed)),
        ));
    }
}
