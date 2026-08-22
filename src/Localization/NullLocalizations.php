<?php

namespace Tempcord\Localization;

/**
 * Used when no translation source is available, so commands register with their
 * declared names and descriptions and nothing else.
 */
final readonly class NullLocalizations implements LocalizationProvider
{
    public function forKey(string $key): array
    {
        return [];
    }
}
