<?php

declare(strict_types=1);

namespace Tempcord\Enums;

/**
 * Discord supported locales
 * @see https://discord.com/developers/docs/reference#locales
 */
enum DiscordLocale: string
{
    //TODO: Commentd out locales are not yet supported by Tempest Intl (@see Tempest\Intl\Locale)
    case Indonesian = 'id';
    case Danish = 'da';
    case German = 'de';
    case EnglishUK = 'en-GB';
    case EnglishUS = 'en-US';
    case Spanish = 'es-ES';
//    case SpanishLATAM = 'es-419';
    case French = 'fr';
    case Croatian = 'hr';
    case Italian = 'it';
    case Lithuanian = 'lt';
    case Hungarian = 'hu';
    case Dutch = 'nl';
    case Norwegian = 'no';
    case Polish = 'pl';
    case PortugueseBrazil = 'pt-BR';
    case Romanian = 'ro';
    case Finnish = 'fi';
    case Swedish = 'sv-SE';
    case Vietnamese = 'vi';
    case Turkish = 'tr';
    case Czech = 'cs';
    case Greek = 'el';
    case Bulgarian = 'bg';
    case Russian = 'ru';
    case Ukrainian = 'uk';
    case Hindi = 'hi';
    case Thai = 'th';
//    case ChineseChina = 'zh-CN';
    case Japanese = 'ja';
//    case ChineseTaiwan = 'zh-TW';
    case Korean = 'ko';

    /**
     * Get all locale codes
     * @return array<string>
     */
    public static function codes(): array
    {
        return array_map(fn(self $locale) => $locale->value, self::cases());
    }

    /**
     * Map common locale codes to Discord locale codes
     */
    public static function normalize(string $locale): ?string
    {
        // Direct match
        $direct = self::tryFrom($locale);
        if ($direct !== null) {
            return $direct->value;
        }

        // Common mappings
        return match (strtolower($locale)) {
            'en', 'english' => self::EnglishUS->value,
            'en_us', 'en-us' => self::EnglishUS->value,
            'en_gb', 'en-gb' => self::EnglishUK->value,
            'es', 'spanish' => self::Spanish->value,
            'pt', 'portuguese', 'pt_br', 'pt-br' => self::PortugueseBrazil->value,
            'zh', 'chinese', 'zh_cn', 'zh-cn' => self::ChineseChina->value,
            'zh_tw', 'zh-tw' => self::ChineseTaiwan->value,
            'sv', 'swedish' => self::Swedish->value,
            default => null,
        };
    }
}
