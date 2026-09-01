<?php

namespace Tempcord\Tests\Unit\Compiler;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use CyberWolf\Discord\Enums\ApplicationCommandOptionType;
use RuntimeException;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Tests\Unit\TestCase;
use Tempcord\AutoCompletes\ArrayAutocomplete;
use Tempcord\Definitions\AutocompleteDefinition;
use Tempcord\Definitions\CommandDefinition;
use Tempcord\Definitions\OptionDefinition;
use Tempcord\Definitions\SubcommandDefinition;
use Tempcord\Definitions\SubcommandGroupDefinition;
use Tempcord\Tests\Fixtures\BareCommand;
use Tempcord\Tests\Fixtures\CommandUserSettings;
use Tempcord\Tests\Fixtures\DescriptionlessCommand;
use Tempcord\Tests\Fixtures\EnumNamedCommand;
use Tempcord\Tests\Fixtures\GuildAlphaCommand;
use Tempcord\Tests\Fixtures\InjectedSearchCommand;
use Tempcord\Tests\Fixtures\NotAnAutocompleteCommand;
use Tempcord\Tests\Fixtures\SearchCommand;
use Tempcord\Tests\Fixtures\SelfCompletingCommand;
use Tempcord\Tests\Fixtures\TrackAutocomplete;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Fixtures\NamedCommand;
use Tempcord\Tests\Fixtures\NoHandlerCommand;
use Tempcord\Tests\Fixtures\OptionTypesCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Fixtures\UnsupportedOptionCommand;
use Tempcord\Tests\Fixtures\UntypedOptionCommand;

#[CoversClass(CommandCompiler::class)]
final class CommandCompilerTest extends TestCase
{
    private function compile(string $class): CommandDefinition
    {
        return $this->definition($class);
    }

    public function test_it_derives_the_name_from_the_class_name(): void
    {
        $this->assertSame('ping', $this->compile(PingCommand::class)->name);
    }

    public function test_an_explicit_name_wins_over_the_class_name(): void
    {
        $this->assertSame('explicit', $this->compile(NamedCommand::class)->name);
    }

    public function test_it_strips_a_command_prefix_as_well_as_a_suffix(): void
    {
        $this->assertSame('user_settings', $this->compile(CommandUserSettings::class)->name);
    }

    public function test_a_backed_enum_name_is_unwrapped(): void
    {
        $this->assertSame('weather', $this->compile(EnumNamedCommand::class)->name);
    }

    public function test_a_guild_id_is_normalised_to_a_string(): void
    {
        $definition = $this->compile(GuildAlphaCommand::class);

        $this->assertSame('111', $definition->guildId);
        $this->assertFalse($definition->isGlobal());
        $this->assertSame('111:alpha', $definition->key());
    }

    public function test_a_global_command_is_keyed_by_name_alone(): void
    {
        $definition = $this->compile(PingCommand::class);

        $this->assertTrue($definition->isGlobal());
        $this->assertSame('ping', $definition->key());
    }

    public function test_an_invokable_command_takes_its_options_from_invoke(): void
    {
        $definition = $this->compile(PingCommand::class);

        $this->assertSame(['name', 'times'], array_keys($definition->options));
        $this->assertInstanceOf(OptionDefinition::class, $definition->options['name']);
        $this->assertTrue($definition->options['name']->isRequired);
        $this->assertFalse($definition->options['times']->isRequired);
    }

    public function test_an_invokable_command_is_handled_under_its_bare_name(): void
    {
        $definition = $this->compile(PingCommand::class);

        $this->assertSame(['ping'], array_keys($definition->handlers));
        $this->assertSame('', $definition->handlers['ping']->optionPath);
        $this->assertSame('name', $definition->handlers['ping']->pathTo($definition->options['name']));
    }

    public function test_an_invokable_command_without_options_still_has_a_handler(): void
    {
        $definition = $this->compile(BareCommand::class);

        $this->assertSame([], $definition->options);
        $this->assertSame(['bare'], array_keys($definition->handlers));
    }

    public function test_ungrouped_subcommands_become_the_commands_options(): void
    {
        $definition = $this->compile(ModerationCommand::class);

        $this->assertSame(['kick'], array_keys($definition->options));
        $this->assertInstanceOf(SubcommandDefinition::class, $definition->options['kick']);
        $this->assertSame(['moderation.kick'], array_keys($definition->handlers));
        $this->assertSame('kick', $definition->handlers['moderation.kick']->optionPath);
    }

    public function test_a_group_nests_its_subcommands_one_level_deeper(): void
    {
        $definition = $this->compile(MusicCommand::class);

        $this->assertSame(['playlist'], array_keys($definition->options));
        $this->assertInstanceOf(SubcommandGroupDefinition::class, $definition->options['playlist']);
        $this->assertSame(['play', 'stop'], array_keys($definition->options['playlist']->subcommands));

        $this->assertSame(
            ['music.playlist.play', 'music.playlist.stop'],
            array_keys($definition->handlers),
        );

        $play = $definition->handlers['music.playlist.play'];
        $this->assertSame('playlist.play', $play->optionPath);
        $this->assertSame('playlist.play.title', $play->pathTo($play->options['title']));
    }

    public function test_it_maps_every_supported_parameter_type(): void
    {
        $options = $this->compile(OptionTypesCommand::class)->options['all']->options;

        $this->assertSame(
            [
                'text' => ApplicationCommandOptionType::STRING,
                'count' => ApplicationCommandOptionType::INTEGER,
                'ratio' => ApplicationCommandOptionType::NUMBER,
                'flag' => ApplicationCommandOptionType::BOOLEAN,
                'user' => ApplicationCommandOptionType::USER,
                'channel' => ApplicationCommandOptionType::CHANNEL,
                'role' => ApplicationCommandOptionType::ROLE,
            ],
            array_map(static fn(OptionDefinition $option) => $option->type, $options),
        );
    }

    public function test_an_explicitly_named_option_keeps_that_name(): void
    {
        $options = $this->compile(OptionTypesCommand::class)->options['renamed']->options;

        $this->assertSame(['custom'], array_keys($options));
    }

    /**
     * Compilation happens at discovery, so a command Discord could never accept
     * fails on boot rather than on the first interaction that reaches it.
     */
    public function test_an_unsupported_parameter_type_is_rejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Command option type not supported');

        $this->compile(UnsupportedOptionCommand::class);
    }

    public function test_an_untyped_parameter_is_rejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Command option does not have type');

        $this->compile(UntypedOptionCommand::class);
    }

    public function test_a_chat_input_command_requires_a_description(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Description for command [descriptionless] is required when type=CHAT_INPUT');

        $this->compile(DescriptionlessCommand::class);
    }

    public function test_a_command_with_neither_subcommands_nor_invoke_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('should declare public sub-commands or have an __invoke method');

        $this->compile(NoHandlerCommand::class);
    }

    /**
     * Compilation happens once, so the tree it returns has stable identity —
     * unlike reading the attribute properties, which rebuilt them per access.
     */
    public function test_the_compiled_tree_is_stable(): void
    {
        $definition = $this->compile(MusicCommand::class);

        $this->assertSame($definition->options['playlist'], $definition->options['playlist']);
        $this->assertSame(
            $definition->options['playlist']->subcommands['play']->options['title'],
            $definition->handlers['music.playlist.play']->options['title'],
        );
    }

    /**
     * The three ways to supply suggestions are told apart at compile time, so
     * the runtime only has to act on what it is given.
     */
    public function test_an_autocomplete_written_inline_is_kept_as_an_object(): void
    {
        $autocomplete = $this->definition(SearchCommand::class)
            ->options['query']->autocomplete;

        $this->assertInstanceOf(AutocompleteDefinition::class, $autocomplete);
        $this->assertInstanceOf(ArrayAutocomplete::class, $autocomplete->instance);
        $this->assertNull($autocomplete->className);
        $this->assertNull($autocomplete->method);
    }

    public function test_an_autocomplete_named_by_class_is_left_for_the_container(): void
    {
        $autocomplete = $this->definition(InjectedSearchCommand::class)
            ->options['track']->autocomplete;

        $this->assertSame(TrackAutocomplete::class, $autocomplete->className);
        $this->assertNull($autocomplete->instance);
    }

    public function test_a_completing_method_is_bound_to_the_option_it_names(): void
    {
        $options = $this->definition(SelfCompletingCommand::class)->options;

        $this->assertSame('completeTrack', $options['track']->autocomplete->method->getName());
        $this->assertSame('completeMood', $options['mood']->autocomplete->method->getName());
    }

    public function test_an_option_nobody_completes_has_no_autocomplete(): void
    {
        $this->assertNull(
            $this->definition(SearchCommand::class)
                ->options['note']->autocomplete
        );
    }

    public function test_a_failing_autocomplete_source_is_named_for_the_log(): void
    {
        $this->assertSame(
            TrackAutocomplete::class,
            $this->definition(InjectedSearchCommand::class)->options['track']->autocomplete->label(),
        );

        $this->assertSame(
            SelfCompletingCommand::class . '::completeTrack()',
            $this->definition(SelfCompletingCommand::class)->options['track']->autocomplete->label(),
        );
    }

    /**
     * A class that is not an autocomplete at all is caught where it is written,
     * rather than when someone first types into the option.
     */
    public function test_a_class_that_is_not_an_autocomplete_is_refused(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('does not implement');

        $this->definition(NotAnAutocompleteCommand::class);
    }
}
