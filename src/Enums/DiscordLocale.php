<?php

namespace Tempcord\Enums;

/**
 * The locales Discord accepts for name and description localizations.
 *
 * Each case carries the Tempest locale it corresponds to as a plain string
 * rather than a Tempest\Intl\Locale case, so this enum stays usable whether or
 * not tempest/intl is installed.
 *
 * @see https://discord.com/developers/docs/reference#locales
 */
enum DiscordLocale: string
{
    case INDONESIAN = 'id';
    case DANISH = 'da';
    case GERMAN = 'de';
    case ENGLISH_UK = 'en-GB';
    case ENGLISH_US = 'en-US';
    case SPANISH = 'es-ES';
    case SPANISH_LATAM = 'es-419';
    case FRENCH = 'fr';
    case CROATIAN = 'hr';
    case ITALIAN = 'it';
    case LITHUANIAN = 'lt';
    case HUNGARIAN = 'hu';
    case DUTCH = 'nl';
    case NORWEGIAN = 'no';
    case POLISH = 'pl';
    case PORTUGUESE_BR = 'pt-BR';
    case ROMANIAN = 'ro';
    case FINNISH = 'fi';
    case SWEDISH = 'sv-SE';
    case VIETNAMESE = 'vi';
    case TURKISH = 'tr';
    case CZECH = 'cs';
    case GREEK = 'el';
    case BULGARIAN = 'bg';
    case RUSSIAN = 'ru';
    case UKRAINIAN = 'uk';
    case HINDI = 'hi';
    case THAI = 'th';
    case CHINESE_CHINA = 'zh-CN';
    case JAPANESE = 'ja';
    case CHINESE_TAIWAN = 'zh-TW';
    case KOREAN = 'ko';
    case ARABIC = 'ar';
    case HEBREW = 'he';

    /**
     * The Tempest locale this maps to.
     *
     * Most differ only in separator — Discord writes en-GB where Tempest writes
     * en_GB. Three have no direct equivalent and are mapped deliberately:
     * Tempest has no Latin America wide Spanish, so es-419 uses the unqualified
     * Spanish catalog while es-ES uses Spain's, and Discord's two Chinese
     * regions map onto Tempest's simplified and traditional scripts.
     */
    public function tempestLocale(): string
    {
        return match ($this) {
            self::SPANISH_LATAM => 'es',
            self::CHINESE_CHINA => 'zh_Hans',
            self::CHINESE_TAIWAN => 'zh_Hant',
            default => str_replace('-', '_', $this->value),
        };
    }
}
