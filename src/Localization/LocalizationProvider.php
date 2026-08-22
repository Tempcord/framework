<?php

namespace Tempcord\Localization;

/**
 * Supplies the translations Discord shows to users in their own language.
 */
interface LocalizationProvider
{
    /**
     * Every Discord locale that has a translation for this key.
     *
     * Locales without one are left out rather than filled in with a fallback,
     * so Discord falls back to the default name or description itself.
     *
     * @return array<string, string> keyed by Discord locale code
     */
    public function forKey(string $key): array;
}
