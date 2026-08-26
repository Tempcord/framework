<?php

namespace Tempcord\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use CyberWolf\Discord\Enums\ApplicationCommandOptionType;
use CyberWolf\Discord\Enums\InteractionCallbackType;
use CyberWolf\Discord\Gateway\Events\InteractionCreate;
use CyberWolf\Discord\Interaction\CommandInteraction;
use CyberWolf\Discord\Parts\ApplicationCommandInteractionDataOptionStructure;
use CyberWolf\Discord\Parts\ApplicationCommandOptionChoice;
use CyberWolf\Discord\Parts\InteractionData;
use Tempcord\Definitions\HandlerDefinition;
use Tempcord\Runtime\AutocompleteResponder;
use Tempcord\Runtime\ChoiceFactory;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Fixtures\SearchCommand;
use Tempcord\Tests\Unit\TestCase;

#[CoversClass(AutocompleteResponder::class)]
final class AutocompleteResponderTest extends TestCase
{
    private RecordingHttp $http;

    protected function setUp(): void
    {
        $this->http = new RecordingHttp();
    }

    private function option(
        string $name,
        mixed $value,
        bool $focused = false,
        array $children = [],
        ApplicationCommandOptionType $type = ApplicationCommandOptionType::STRING,
    ): ApplicationCommandInteractionDataOptionStructure {
        $option = new ApplicationCommandInteractionDataOptionStructure();
        $option->name = $name;
        $option->type = $type;
        $option->value = $value;
        $option->options = $children;
        $option->focused = $focused;

        return $option;
    }

    private function interaction(string $command, array $options): CommandInteraction
    {
        $data = new InteractionData();
        $data->name = $command;
        $data->options = $options;

        $interaction = new InteractionCreate();
        $interaction->id = '1';
        $interaction->token = 'token';
        $interaction->data = $data;

        return new CommandInteraction($interaction, new FakeDiscord($this->http));
    }

    private function handler(string $class, string $path): HandlerDefinition
    {
        return $this->definition($class)->handlers[$path];
    }

    private function respond(HandlerDefinition $handler, CommandInteraction $interaction): void
    {
        new AutocompleteResponder(new ChoiceFactory())->respond($handler, $interaction);
    }

    /**
     * @return list<array{string, mixed}> each choice as a name/value pair
     */
    private function sentChoices(): array
    {
        return array_map(
            static fn(ApplicationCommandOptionChoice $choice) => [$choice->name, $choice->value],
            $this->http->posts[0]['content']['data']['choices'] ?? [],
        );
    }

    public function test_it_answers_the_focused_option_with_its_suggestions(): void
    {
        $this->respond(
            $this->handler(SearchCommand::class, 'search'),
            $this->interaction('search', [$this->option('query', 'a', focused: true)]),
        );

        $this->assertSame(['interactions/1/token/callback'], $this->http->postedUrls());
        $this->assertSame(
            InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT->value,
            $this->http->posts[0]['content']['type'],
        );
        $this->assertSame(
            [['alpha', 'alpha'], ['beta', 'beta'], ['gamma', 'gamma']],
            $this->sentChoices(),
        );
    }

    /**
     * Discord sends autocomplete interactions while the user is still typing,
     * and they can arrive with nothing focused at all.
     */
    public function test_an_interaction_with_nothing_focused_is_ignored(): void
    {
        $this->respond(
            $this->handler(SearchCommand::class, 'search'),
            $this->interaction('search', [$this->option('query', 'a')]),
        );

        $this->assertSame([], $this->http->posts);
    }

    public function test_an_interaction_with_no_options_at_all_is_ignored(): void
    {
        $this->respond(
            $this->handler(SearchCommand::class, 'search'),
            $this->interaction('search', []),
        );

        $this->assertSame([], $this->http->posts);
    }

    public function test_a_focused_option_the_handler_does_not_declare_is_ignored(): void
    {
        $this->respond(
            $this->handler(SearchCommand::class, 'search'),
            $this->interaction('search', [$this->option('not_declared', 'a', focused: true)]),
        );

        $this->assertSame([], $this->http->posts);
    }

    public function test_a_focused_option_without_an_autocomplete_is_ignored(): void
    {
        $this->respond(
            $this->handler(SearchCommand::class, 'search'),
            $this->interaction('search', [$this->option('note', 'a', focused: true)]),
        );

        $this->assertSame([], $this->http->posts);
    }

    public function test_it_finds_an_option_focused_under_a_group_and_subcommand(): void
    {
        $this->respond(
            $this->handler(MusicCommand::class, 'music.playlist.play'),
            $this->interaction('music', [
                $this->option('playlist', null, children: [
                    $this->option('play', null, children: [
                        $this->option('title', 'boh', focused: true),
                    ], type: ApplicationCommandOptionType::SUB_COMMAND),
                ], type: ApplicationCommandOptionType::SUB_COMMAND_GROUP),
            ]),
        );

        // MusicCommand declares no autocomplete, so it resolves and then stops.
        $this->assertSame([], $this->http->posts);
    }
}
