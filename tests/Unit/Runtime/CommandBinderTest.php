<?php

namespace Tempcord\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Discord\Enums\ApplicationCommandOptionType;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\ApplicationCommandInteractionDataOptionStructure;
use Tempcord\Discord\Parts\InteractionData;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Runtime\CommandBinder;
use Tempcord\Runtime\Outcome;
use Tempcord\Runtime\OutcomeLevel;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Fixtures\RecordingCommand;
use Tempcord\Tests\Unit\TestCase;

#[CoversClass(CommandBinder::class)]
final class CommandBinderTest extends TestCase
{
    private function binder(): CommandBinder
    {
        return new \ReflectionProperty(\Tempcord\Tempcord::class, 'binder')
            ->getValue($this->tempcord());
    }

    /** @return list<string> */
    private function messages(array $outcomes): array
    {
        return array_map(static fn(Outcome $outcome) => $outcome->message, $outcomes);
    }

    public function test_it_binds_one_handler_per_interaction_path(): void
    {
        $registry = new CommandsRegistry();
        $registry->add($this->definition(MusicCommand::class));

        $this->assertSame(
            ['Command "music.playlist.play" listened.', 'Command "music.playlist.stop" listened.'],
            $this->messages($this->binder()->bindAll($registry->all())),
        );
    }

    public function test_it_warns_when_there_is_nothing_to_bind(): void
    {
        $outcomes = $this->binder()->bindAll([]);

        $this->assertCount(1, $outcomes);
        $this->assertSame(OutcomeLevel::Warning, $outcomes[0]->level);
    }

    public function test_a_bound_command_answers_its_own_interaction_path(): void
    {
        $binder = $this->binder();
        $registry = new CommandsRegistry();
        $registry->add($this->definition(ModerationCommand::class));

        $binder->bindAll($registry->all());

        $this->assertCount(1, $binder->extension->listeners('moderation.kick'));
        $this->assertCount(1, $binder->extension->listeners('moderation.kick.autocomplete'));
    }

    /**
     * The whole path, end to end: a compiled command is bound, an interaction
     * arrives under the name Discord reports, and the method runs with the
     * option the user supplied.
     */
    public function test_an_interaction_reaches_the_command_it_names(): void
    {
        RecordingCommand::$calls = [];

        $binder = $this->binder();
        $registry = new CommandsRegistry();
        $registry->add($this->definition(RecordingCommand::class));

        $binder->bindAll($registry->all());

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

        $binder->extension->emit('recording', [
            new CommandInteraction($interaction, new FakeDiscord(new RecordingHttp())),
        ]);

        $this->assertSame(['end to end'], RecordingCommand::$calls);
    }
}
