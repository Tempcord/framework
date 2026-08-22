<?php

namespace Tempcord\Tests\Doubles;

use Tempcord\Localization\LocalizationProvider;

/**
 * A translation catalog held in memory, so localization can be exercised
 * without tempest/intl or its extensions being installed.
 */
final class FakeLocalizations implements LocalizationProvider
{
    /** @var list<string> every key that was asked for, in order */
    public array $requested = [];

    /**
     * @param array<string, array<string, string>> $translations keyed by catalog
     *        key, then by Discord locale
     */
    public function __construct(
        private readonly array $translations = [],
    ) {}

    public function forKey(string $key): array
    {
        $this->requested[] = $key;

        return $this->translations[$key] ?? [];
    }
}
