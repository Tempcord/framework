<?php

namespace Tempcord\Localization;

use Tempcord\Enums\DiscordLocale;
use Tempest\Intl\Catalog\Catalog;
use Tempest\Intl\Locale;

/**
 * Reads translations out of Tempest's own catalog, so command translations live
 * in the same files as the rest of the application's.
 *
 * This is the only class that touches tempest/intl. It is wired up when that
 * package is installed and skipped otherwise.
 */
final readonly class CatalogLocalizations implements LocalizationProvider
{
    public function __construct(
        private Catalog $catalog,
    ) {}

    public function forKey(string $key): array
    {
        $translations = [];

        foreach (DiscordLocale::cases() as $discordLocale) {
            $locale = Locale::tryFrom($discordLocale->tempestLocale());

            /*
             * Asked rather than translated: a translator returns the key itself
             * when a message is missing, which would show up in Discord as the
             * translation.
             */
            if ($locale === null || !$this->catalog->has($locale, $key)) {
                continue;
            }

            $message = $this->catalog->get($locale, $key);

            if ($message !== null) {
                $translations[$discordLocale->value] = $message;
            }
        }

        return $translations;
    }
}
