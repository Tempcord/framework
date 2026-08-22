<?php

namespace Tempcord\Tests\Unit\Localization;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Enums\DiscordLocale;
use Tempcord\Localization\CatalogLocalizations;
use Tempest\Intl\Catalog\GenericCatalog;
use Tempest\Intl\Locale;

#[CoversClass(CatalogLocalizations::class)]
#[CoversClass(DiscordLocale::class)]
final class CatalogLocalizationsTest extends BaseTestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(\Tempest\Intl\Catalog\Catalog::class)) {
            $this->markTestSkipped('tempest/intl is not installed');
        }
    }

    public function test_it_reads_translations_out_of_the_catalog(): void
    {
        $catalog = new GenericCatalog()
            ->add(Locale::GERMAN, 'commands.greet.description', 'Begrüßt jemanden')
            ->add(Locale::FRENCH, 'commands.greet.description', 'Salue quelqu\'un');

        $this->assertSame(
            ['de' => 'Begrüßt jemanden', 'fr' => 'Salue quelqu\'un'],
            new CatalogLocalizations($catalog)->forKey('commands.greet.description'),
        );
    }

    /**
     * A translator hands back the key itself when a message is missing, which
     * would show up in Discord as the translation. Absent locales are left out
     * so Discord falls back to the declared text.
     */
    public function test_a_missing_translation_is_left_out_rather_than_filled_in(): void
    {
        $catalog = new GenericCatalog()->add(Locale::GERMAN, 'known', 'Bekannt');

        $this->assertSame([], new CatalogLocalizations($catalog)->forKey('unknown'));
        $this->assertSame(['de' => 'Bekannt'], new CatalogLocalizations($catalog)->forKey('known'));
    }

    /**
     * Discord writes en-GB where Tempest writes en_GB, and the result has to
     * carry Discord's spelling.
     */
    public function test_region_locales_are_keyed_by_discords_spelling(): void
    {
        $catalog = new GenericCatalog()
            ->add(Locale::ENGLISH_UNITED_KINGDOM, 'k', 'Colour')
            ->add(Locale::PORTUGUESE_BRAZIL, 'k', 'Cor');

        $this->assertSame(
            ['en-GB' => 'Colour', 'pt-BR' => 'Cor'],
            new CatalogLocalizations($catalog)->forKey('k'),
        );
    }

    /**
     * Three Discord locales have no direct Tempest equivalent and are mapped
     * deliberately rather than skipped.
     */
    public function test_the_locales_without_a_direct_equivalent_are_mapped(): void
    {
        $catalog = new GenericCatalog()
            ->add(Locale::SPANISH, 'k', 'Hola')
            ->add(Locale::CHINESE_SIMPLIFIED, 'k', '简体')
            ->add(Locale::CHINESE_TRADITIONAL, 'k', '繁體');

        $translations = new CatalogLocalizations($catalog)->forKey('k');

        $this->assertSame('Hola', $translations['es-419']);
        $this->assertSame('简体', $translations['zh-CN']);
        $this->assertSame('繁體', $translations['zh-TW']);
    }

    public function test_every_discord_locale_maps_onto_a_real_tempest_locale(): void
    {
        foreach (DiscordLocale::cases() as $discordLocale) {
            $this->assertNotNull(
                Locale::tryFrom($discordLocale->tempestLocale()),
                $discordLocale->value . ' maps to ' . $discordLocale->tempestLocale() . ', which Tempest does not define',
            );
        }
    }
}
