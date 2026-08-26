<?php

namespace Tempcord\Tests\Unit\Localization;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Attributes\Command;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Definitions\CommandDefinition;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Localization\NullLocalizations;
use Tempcord\Tests\Doubles\FakeLocalizations;
use Tempcord\Tests\Fixtures\LocalizedCommand;
use Tempcord\Tests\Fixtures\LocalizedInvokableCommand;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Reflection\ClassReflector;

#[CoversClass(CommandCompiler::class)]
final class LocalizationTest extends TestCase
{
    private function compile(string $class, FakeLocalizations $localizations): CommandDefinition
    {
        $reflector = new ClassReflector($class);

        /** @var Command $attribute */
        $attribute = $reflector->getAttribute(Command::class);

        return new CommandCompiler($localizations)->compile($reflector, $attribute);
    }

    /**
     * Keys follow the shape of the command tree, so one key on the command
     * covers everything beneath it.
     */
    public function test_keys_are_derived_from_position_in_the_tree(): void
    {
        $localizations = new FakeLocalizations();

        $this->compile(LocalizedCommand::class, $localizations);

        $this->assertContains('commands.music.description', $localizations->requested);
        $this->assertContains('commands.music.playlist.description', $localizations->requested);
        $this->assertContains('commands.music.playlist.play.description', $localizations->requested);
        $this->assertContains('commands.music.playlist.play.title.description', $localizations->requested);
    }

    public function test_an_invokable_commands_options_hang_directly_off_its_key(): void
    {
        $localizations = new FakeLocalizations();

        $this->compile(LocalizedInvokableCommand::class, $localizations);

        $this->assertContains('commands.greet.description', $localizations->requested);
        $this->assertContains('commands.greet.name.description', $localizations->requested);
    }

    public function test_names_are_localized_as_well_as_descriptions(): void
    {
        $localizations = new FakeLocalizations();

        $this->compile(LocalizedInvokableCommand::class, $localizations);

        $this->assertContains('commands.greet.name', $localizations->requested);
        $this->assertContains('commands.greet.name.name', $localizations->requested);
    }

    public function test_translations_reach_the_definition(): void
    {
        $definition = $this->compile(LocalizedInvokableCommand::class, new FakeLocalizations([
            'commands.greet.description' => ['de' => 'Begrüßt jemanden'],
            'commands.greet.name.description' => ['de' => 'Wen begrüßen'],
        ]));

        $this->assertSame(['de' => 'Begrüßt jemanden'], $definition->descriptionLocalizations);
        $this->assertSame(['de' => 'Wen begrüßen'], $definition->options['name']->descriptionLocalizations);
    }

    /**
     * A command that declares no key must not ask for translations at all,
     * rather than asking for keys derived from its name.
     */
    public function test_a_command_without_a_key_localizes_nothing(): void
    {
        $localizations = new FakeLocalizations();

        $definition = $this->compile(MusicCommand::class, $localizations);

        $this->assertSame([], $localizations->requested);
        $this->assertSame([], $definition->descriptionLocalizations);
        $this->assertSame([], $definition->nameLocalizations);
    }

    public function test_the_default_provider_returns_nothing(): void
    {
        $this->assertSame([], new NullLocalizations()->forKey('anything'));
    }

    public function test_localizations_reach_the_payload(): void
    {
        $definition = $this->compile(LocalizedCommand::class, new FakeLocalizations([
            'commands.music.description' => ['de' => 'Musiksteuerung'],
            'commands.music.playlist.description' => ['de' => 'Wiedergabeliste'],
            'commands.music.playlist.play.description' => ['de' => 'Titel abspielen'],
            'commands.music.playlist.play.title.description' => ['de' => 'Titelname'],
            'commands.music.playlist.play.title.name' => ['de' => 'titel'],
        ]));

        $built = new CommandBuilderFactory()->payloadFor($definition);

        $this->assertSame(['de' => 'Musiksteuerung'], $built['description_localizations']);

        $group = $built['options'][0];
        $this->assertSame(['de' => 'Wiedergabeliste'], $group['description_localizations']);

        $play = $group['options'][0];
        $this->assertSame(['de' => 'Titel abspielen'], $play['description_localizations']);

        $title = $play['options'][0];
        $this->assertSame(['de' => 'Titelname'], $title['description_localizations']);
        $this->assertSame(['de' => 'titel'], $title['name_localizations']);
    }

    /**
     * Nothing empty is sent; Discord falls back to the declared text itself.
     */
    public function test_a_command_without_translations_sends_no_localization_keys(): void
    {
        $built = new CommandBuilderFactory()->payloadFor($this->definition(MusicCommand::class));

        $this->assertArrayNotHasKey('description_localizations', $built);
        $this->assertArrayNotHasKey('name_localizations', $built);
        $this->assertArrayNotHasKey('description_localizations', $built['options'][0]);
    }
}
