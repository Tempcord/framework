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
use Tempcord\Runtime\AutocompleteResolver;
use Tempcord\Runtime\AutocompleteResponder;
use Tempcord\Runtime\ChoiceFactory;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempest\Container\GenericContainer;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Fixtures\InjectedSearchCommand;
use Tempcord\Tests\Fixtures\SearchCommand;
use Tempcord\Tests\Fixtures\SelfCompletingCommand;
use Tempcord\Tests\Fixtures\ThrowingAutocompleteCommand;
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

    private RecordingLogger $logger;

    private function respond(HandlerDefinition $handler, CommandInteraction $interaction): void
    {
        $this->logger = new RecordingLogger();

        new AutocompleteResponder(
            new ChoiceFactory(),
            new AutocompleteResolver(new GenericContainer()),
            $this->logger,
        )->respond($handler, $interaction);
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

    /**
     * An autocomplete named by class is built by the container, which is the
     * only way it can have dependencies of its own.
     */
    public function test_an_autocomplete_named_by_class_is_built_with_its_dependencies(): void
    {
        $this->respond(
            $this->handler(InjectedSearchCommand::class, 'injected_search'),
            $this->interaction('injected_search', [$this->option('track', 'kal', focused: true)]),
        );

        $this->assertSame([['Kalush', 'Kalush']], $this->sentChoices());
    }

    /**
     * A method on the command needs no separate class and reaches the command's
     * own dependencies.
     */
    public function test_a_completing_method_on_the_command_supplies_suggestions(): void
    {
        $this->respond(
            $this->handler(SelfCompletingCommand::class, 'self_completing'),
            $this->interaction('self_completing', [$this->option('track', 'boom', focused: true)]),
        );

        $this->assertSame([['Boombox', 'Boombox']], $this->sentChoices());
    }

    /**
     * A completing method takes what it asks for, in whatever order it asks.
     */
    public function test_a_completing_method_is_given_the_interaction_when_it_asks_for_one(): void
    {
        $this->respond(
            $this->handler(SelfCompletingCommand::class, 'self_completing'),
            $this->interaction('self_completing', [$this->option('mood', 'calm', focused: true)]),
        );

        $this->assertSame([['1', '1'], ['calm', 'calm']], $this->sentChoices());
    }

    /**
     * Suggestions often come from a database or an API, so one that fails must
     * be contained: Discord simply shows nothing rather than the gateway
     * falling over.
     */
    public function test_an_autocomplete_that_throws_is_logged_and_answered_with_nothing(): void
    {
        $this->respond(
            $this->handler(ThrowingAutocompleteCommand::class, 'throwing_autocomplete'),
            $this->interaction('throwing_autocomplete', [$this->option('query', 'a', focused: true)]),
        );

        $this->assertSame([], $this->http->posts);
        $this->assertStringContainsString('failed: no suggestions today', $this->logger->messages[0]);
    }

    public function test_a_failing_autocomplete_is_reported_against_what_wrote_it(): void
    {
        $this->respond(
            $this->handler(ThrowingAutocompleteCommand::class, 'throwing_autocomplete'),
            $this->interaction('throwing_autocomplete', [$this->option('query', 'a', focused: true)]),
        );

        $this->assertStringContainsString(
            ThrowingAutocompleteCommand::class . '::completeQuery()',
            $this->logger->messages[0],
        );
    }
}
