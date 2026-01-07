<?php

declare(strict_types=1);

namespace Tempcord\Support\Localization;

use Tempcord\Enums\DiscordLocale;
use Tempest\Container\Singleton;
use Tempest\Intl\Locale;
use Tempest\Intl\Translator;

/**
 * Simple bridge between Tempest's Translator and Discord's localization
 *
 * Auto-detects translation keys and resolves them for all available locales.
 * Uses Tempest's standard translation files (e.g., commands.en.yaml, commands.uk.yaml)
 */
#[Singleton]
class CommandTranslator
{
    public function __construct(
        private readonly Translator $translator
    )
    {
    }

    /**
     * Check if a string looks like a translation key
     * (contains dots, no spaces, not a regular sentence)
     */
    public function isTranslationKey(string $value): bool
    {
        return str_contains($value, '.') && !str_contains($value, ' ');
    }

    /**
     * Resolve a value - returns translation if it's a key, otherwise returns as-is
     */
    public function resolve(string $value, ?string $locale = null): string
    {
        if (!$this->isTranslationKey($value)) {
            return $value;
        }


        return $this->translator->translate($value, $locale) ?? $value;
    }

    /**
     * Get translations for all Discord locales
     * Returns array of [discordLocale => translatedValue]
     */
    public function getLocalizations(string $key): array
    {
        if (!$this->isTranslationKey($key)) {
            return [];
        }

        $localizations = [];

        foreach (DiscordLocale::cases() as $discordLocale) {
            // Map Discord locale to Tempest locale (e.g., 'en-US' -> 'en', 'uk' -> 'uk')
            $tempestLocale = str_replace(['-'], ['_'], $discordLocale->value);

            $translation = $this->translator->translateForLocale(
                locale: Locale::from($tempestLocale),
                key: $key
            );

            // Only add if translation exists and differs from key
            if ($translation !== $key) {
                $localizations[$discordLocale->value] = $translation;
            }
        }

        return $localizations;
    }
}
