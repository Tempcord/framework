<?php

declare(strict_types=1);

namespace Tempcord\Support\Localization;

use Tempcord\Enums\DiscordLocale;
use Tempest\Container\Singleton;

/**
 * Handles localization for Discord commands
 *
 * Translation files structure:
 * lang/
 *   en-US/
 *     commands.php  -> ['ping' => ['name' => 'ping', 'description' => 'Check bot latency']]
 *   es-ES/
 *     commands.php  -> ['ping' => ['name' => 'ping', 'description' => 'Comprobar la latencia del bot']]
 */
#[Singleton]
class CommandTranslator
{
    /** @var array<string, array<string, mixed>> Cached translations by locale */
    private array $translations = [];

    /** @var string Base path for language files */
    private string $langPath;

    /** @var string Default locale */
    private string $defaultLocale = 'en-US';

    public function __construct(?string $langPath = null)
    {
        $this->langPath = $langPath ?? $this->detectLangPath();
    }

    /**
     * Set the language files path
     */
    public function setLangPath(string $path): self
    {
        $this->langPath = $path;
        $this->translations = []; // Clear cache
        return $this;
    }

    /**
     * Set the default locale
     */
    public function setDefaultLocale(string $locale): self
    {
        $this->defaultLocale = DiscordLocale::normalize($locale) ?? $locale;
        return $this;
    }

    /**
     * Get translations for a command across all available locales
     *
     * @param string $commandKey Translation key (e.g., 'ping' or 'user.info')
     * @return array<string, array{name?: string, description?: string}>
     */
    public function getCommandLocalizations(string $commandKey): array
    {
        $localizations = [];

        foreach (DiscordLocale::cases() as $locale) {
            $translation = $this->getTranslation($locale->value, "commands.{$commandKey}");

            if ($translation !== null && is_array($translation)) {
                $localized = [];

                if (isset($translation['name'])) {
                    $localized['name'] = $translation['name'];
                }

                if (isset($translation['description'])) {
                    $localized['description'] = $translation['description'];
                }

                if (!empty($localized)) {
                    $localizations[$locale->value] = $localized;
                }
            }
        }

        return $localizations;
    }

    /**
     * Get option translations for a command
     *
     * @param string $commandKey Command translation key
     * @param string $optionName Option name
     * @return array<string, array{name?: string, description?: string}>
     */
    public function getOptionLocalizations(string $commandKey, string $optionName): array
    {
        $localizations = [];

        foreach (DiscordLocale::cases() as $locale) {
            $translation = $this->getTranslation(
                $locale->value,
                "commands.{$commandKey}.options.{$optionName}"
            );

            if ($translation !== null && is_array($translation)) {
                $localized = [];

                if (isset($translation['name'])) {
                    $localized['name'] = $translation['name'];
                }

                if (isset($translation['description'])) {
                    $localized['description'] = $translation['description'];
                }

                if (!empty($localized)) {
                    $localizations[$locale->value] = $localized;
                }
            }
        }

        return $localizations;
    }

    /**
     * Get choice translations for an option
     *
     * @param string $commandKey Command translation key
     * @param string $optionName Option name
     * @param string $choiceValue Choice value
     * @return array<string, string> Locale => translated name
     */
    public function getChoiceLocalizations(string $commandKey, string $optionName, string $choiceValue): array
    {
        $localizations = [];

        foreach (DiscordLocale::cases() as $locale) {
            $translation = $this->getTranslation(
                $locale->value,
                "commands.{$commandKey}.options.{$optionName}.choices.{$choiceValue}"
            );

            if ($translation !== null && is_string($translation)) {
                $localizations[$locale->value] = $translation;
            }
        }

        return $localizations;
    }

    /**
     * Translate a key for a specific locale
     */
    public function translate(string $key, string $locale = null, array $replace = []): ?string
    {
        $locale = $locale ?? $this->defaultLocale;
        $translation = $this->getTranslation($locale, $key);

        if ($translation === null || !is_string($translation)) {
            // Try default locale as fallback
            if ($locale !== $this->defaultLocale) {
                $translation = $this->getTranslation($this->defaultLocale, $key);
            }
        }

        if ($translation === null || !is_string($translation)) {
            return null;
        }

        // Replace placeholders
        foreach ($replace as $search => $value) {
            $translation = str_replace(":{$search}", (string) $value, $translation);
        }

        return $translation;
    }

    /**
     * Check if a key looks like a translation key (contains dots or specific format)
     */
    public function isTranslationKey(string $value): bool
    {
        // If it starts with 'commands.' or contains a dot, treat as translation key
        return str_starts_with($value, 'commands.')
            || str_starts_with($value, 'options.')
            || (str_contains($value, '.') && !str_contains($value, ' '));
    }

    /**
     * Get a translation value by key
     */
    private function getTranslation(string $locale, string $key): mixed
    {
        $this->loadLocale($locale);

        if (!isset($this->translations[$locale])) {
            return null;
        }

        $parts = explode('.', $key);
        $value = $this->translations[$locale];

        foreach ($parts as $part) {
            if (!is_array($value) || !isset($value[$part])) {
                return null;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Load translations for a locale
     */
    private function loadLocale(string $locale): void
    {
        if (isset($this->translations[$locale])) {
            return;
        }

        $this->translations[$locale] = [];
        $localePath = $this->langPath . '/' . $locale;

        if (!is_dir($localePath)) {
            // Try normalized locale
            $normalized = DiscordLocale::normalize($locale);
            if ($normalized !== null && $normalized !== $locale) {
                $localePath = $this->langPath . '/' . $normalized;
            }
        }

        if (!is_dir($localePath)) {
            return;
        }

        // Load all PHP files in the locale directory
        $files = glob($localePath . '/*.php');

        foreach ($files as $file) {
            $group = basename($file, '.php');
            $content = require $file;

            if (is_array($content)) {
                $this->translations[$locale][$group] = $content;
            }
        }
    }

    /**
     * Detect the language files path
     */
    private function detectLangPath(): string
    {
        // Check common locations
        $possiblePaths = [
            getcwd() . '/lang',
            getcwd() . '/resources/lang',
            getcwd() . '/app/lang',
            dirname(__DIR__, 4) . '/lang', // Relative to vendor
        ];

        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        // Default to ./lang even if it doesn't exist yet
        return getcwd() . '/lang';
    }

    /**
     * Get available locales (locales with translation files)
     * @return array<string>
     */
    public function getAvailableLocales(): array
    {
        if (!is_dir($this->langPath)) {
            return [];
        }

        $locales = [];
        $dirs = glob($this->langPath . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $locale = basename($dir);
            if (DiscordLocale::tryFrom($locale) !== null) {
                $locales[] = $locale;
            }
        }

        return $locales;
    }
}
