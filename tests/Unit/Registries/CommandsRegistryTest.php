<?php

namespace Tempcord\Tests\Unit\Registries;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Ragnarok\Fenrir\Parts\InteractionData;
use ReflectionProperty;
use Tempcord\Definitions\CommandDefinition;
use Tempcord\Discord\AllCommandExtension;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Runtime\ArgumentResolver;
use Tempcord\Runtime\AutocompleteResponder;
use Tempcord\Runtime\ChoiceFactory;
use Tempcord\Runtime\CommandDispatcher;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\Runtime\Outcome;
use Tempcord\Runtime\OutcomeLevel;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\GuildAlphaCommand;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Fixtures\RecordingCommand;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Container\GenericContainer;

#[CoversClass(CommandsRegistry::class)]
final class CommandsRegistryTest extends TestCase
{
    private function registry(?AllCommandExtension $extension = null): CommandsRegistry
    {
        $discord = new FakeDiscord(new RecordingHttp());

        return new CommandsRegistry(
            extension: $extension ?? new AllCommandExtension(),
            builders: new CommandBuilderFactory(),
            dispatcher: new CommandDispatcher(
                new ArgumentResolver(new OptionValueResolver($discord)),
                new GenericContainer(),
                new NullLogger(),
            ),
            autocomplete: new AutocompleteResponder(new ChoiceFactory()),
        );
    }

    /** @return array<string, CommandDefinition> */
    private function stored(CommandsRegistry $registry): array
    {
        return new ReflectionProperty(CommandsRegistry::class, 'commands')->getValue($registry);
    }

    /** @return list<string> */
    private function messages(array $outcomes): array
    {
        return array_map(static fn(Outcome $outcome) => $outcome->message, $outcomes);
    }

    public function test_it_stores_a_global_command_under_its_name(): void
    {
        $registry = $this->registry();
        $registry->add($this->definition(PingCommand::class));

        $this->assertSame(['ping'], array_keys($this->stored($registry)));
    }

    public function test_it_scopes_a_guild_command_by_its_guild(): void
    {
        $registry = $this->registry();
        $registry->add($this->definition(GuildAlphaCommand::class));

        $this->assertSame(['111:alpha'], array_keys($this->stored($registry)));
    }

    public function test_it_keeps_distinct_commands_side_by_side(): void
    {
        $registry = $this->registry();
        $registry->add($this->definition(PingCommand::class));
        $registry->add($this->definition(ModerationCommand::class));

        $this->assertSame(['ping', 'moderation'], array_keys($this->stored($registry)));
    }

    public function test_re_adding_the_same_command_name_merges_it(): void
    {
        $registry = $this->registry();
        $registry->add($this->definition(PingCommand::class));
        $registry->add($this->definition(PingCommand::class));

        $stored = $this->stored($registry);

        $this->assertCount(1, $stored);
        $this->assertSame(['name', 'times'], array_keys($stored['ping']->options));
        $this->assertSame(['ping'], array_keys($stored['ping']->handlers));
    }

    public function test_listen_binds_one_handler_per_interaction_path(): void
    {
        $registry = $this->registry();
        $registry->add($this->definition(MusicCommand::class));

        $this->assertSame(
            ['Command "music.playlist.play" listened.', 'Command "music.playlist.stop" listened.'],
            $this->messages($registry->listen()),
        );
    }

    public function test_listen_warns_when_there_is_nothing_to_bind(): void
    {
        $outcomes = $this->registry()->listen();

        $this->assertCount(1, $outcomes);
        $this->assertSame(OutcomeLevel::Warning, $outcomes[0]->level);
    }

    /**
     * The whole path, end to end: a compiled command is bound, an interaction
     * arrives under the name Discord reports, and the method runs with the
     * option the user supplied.
     */
    public function test_an_interaction_reaches_the_command_it_names(): void
    {
        RecordingCommand::$calls = [];

        $extension = new AllCommandExtension();
        $registry = $this->registry($extension);
        $registry->add($this->definition(RecordingCommand::class));
        $registry->listen();

        $data = new InteractionData();
        $data->name = 'recording';

        $subject = new ApplicationCommandInteractionDataOptionStructure();
        $subject->name = 'subject';
        $subject->type = ApplicationCommandOptionType::STRING;
        $subject->value = 'end to end';
        $subject->options = [];
        $data->options = [$subject];

        $interaction = new InteractionCreate();
        $interaction->id = '1';
        $interaction->token = 'token';
        $interaction->data = $data;

        $extension->emit('recording', [
            new CommandInteraction($interaction, new FakeDiscord(new RecordingHttp())),
        ]);

        $this->assertSame(['end to end'], RecordingCommand::$calls);
    }

    public function test_a_bound_command_answers_its_own_interaction_path(): void
    {
        $extension = new AllCommandExtension();
        $registry = $this->registry($extension);
        $registry->add($this->definition(ModerationCommand::class));
        $registry->listen();

        $this->assertCount(1, $extension->listeners('moderation.kick'));
        $this->assertCount(1, $extension->listeners('moderation.kick.autocomplete'));
    }
}
