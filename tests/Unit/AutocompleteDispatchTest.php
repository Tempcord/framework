<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Ragnarok\Fenrir\Parts\InteractionData;
use Tempcord\AllCommandExtension;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempest\Console\Console;

#[CoversClass(CommandsRegistry::class)]
final class AutocompleteDispatchTest extends TestCase
{
    private function interaction(FakeDiscord $discord, array $options): CommandInteraction
    {
        $data = new InteractionData();
        $data->name = 'ping';
        $data->options = $options;

        $interaction = new InteractionCreate();
        $interaction->id = '1';
        $interaction->token = 'token';
        $interaction->data = $data;

        return new CommandInteraction($interaction, $discord);
    }

    /**
     * Runs one autocomplete interaction against a listening PingCommand and
     * returns every Discord endpoint it posted to.
     *
     * @return list<string>
     */
    private function dispatch(array $options): array
    {
        $http = new RecordingHttp();

        $extension = new AllCommandExtension();
        $registry = new CommandsRegistry($extension);
        $registry->add($this->command(PingCommand::class));
        $registry->listen($this->createStub(Console::class));

        $extension->emit('ping.autocomplete', [
            $this->interaction(new FakeDiscord($http), $options),
        ]);

        return $http->postedUrls();
    }

    private function option(string $name, bool $focused = false): ApplicationCommandInteractionDataOptionStructure
    {
        $option = new ApplicationCommandInteractionDataOptionStructure();
        $option->name = $name;
        $option->type = ApplicationCommandOptionType::STRING;
        $option->value = 'wh';
        $option->options = [];
        $option->focused = $focused;

        return $option;
    }

    /**
     * Discord sends an autocomplete interaction while the user is still typing,
     * and it can arrive with nothing focused. That must not take the bot down.
     */
    public function test_an_autocomplete_with_nothing_focused_is_ignored(): void
    {
        $this->assertSame([], $this->dispatch([$this->option('name'), $this->option('times')]));
    }

    public function test_an_autocomplete_focused_on_an_undeclared_option_is_ignored(): void
    {
        $this->assertSame([], $this->dispatch([$this->option('not_declared', focused: true)]));
    }

    /**
     * PingCommand declares no autocomplete on its options, so a focused option
     * still produces no response — but it must get there without erroring.
     */
    public function test_an_option_without_an_autocomplete_produces_no_response(): void
    {
        $this->assertSame([], $this->dispatch([$this->option('name', focused: true)]));
    }
}
