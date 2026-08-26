<?php

namespace Tempcord\Tests\Unit\Runtime;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use CyberWolf\Discord\Enums\ApplicationCommandOptionType;
use CyberWolf\Discord\Gateway\Events\InteractionCreate;
use CyberWolf\Discord\Interaction\CommandInteraction;
use CyberWolf\Discord\Parts\ApplicationCommandInteractionDataOptionStructure;
use CyberWolf\Discord\Parts\InteractionData;
use Tempcord\Definitions\HandlerDefinition;
use Tempcord\Runtime\ArgumentResolver;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Fixtures\OptionTypesCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Unit\TestCase;

#[CoversClass(ArgumentResolver::class)]
final class ArgumentResolverTest extends TestCase
{
    private function resolver(): ArgumentResolver
    {
        return new ArgumentResolver(
            new OptionValueResolver(new FakeDiscord(new RecordingHttp())),
        );
    }

    private function option(string $name, mixed $value, array $children = [], ApplicationCommandOptionType $type = ApplicationCommandOptionType::STRING): ApplicationCommandInteractionDataOptionStructure
    {
        $option = new ApplicationCommandInteractionDataOptionStructure();
        $option->name = $name;
        $option->type = $type;
        $option->value = $value;
        $option->options = $children;

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
        $interaction->guild_id = '999';
        $interaction->data = $data;

        return new CommandInteraction($interaction, new FakeDiscord(new RecordingHttp()));
    }

    private function handler(string $class, string $path): HandlerDefinition
    {
        return $this->definition($class)->handlers[$path];
    }

    public function test_it_orders_arguments_to_the_method_signature(): void
    {
        $handler = $this->handler(PingCommand::class, 'ping');
        $interaction = $this->interaction('ping', [
            $this->option('times', 3, type: ApplicationCommandOptionType::INTEGER),
            $this->option('name', 'Ada'),
        ]);

        $arguments = $this->resolver()->resolve($handler, $interaction);

        $this->assertSame([$interaction, 'Ada', 3], $arguments);
    }

    public function test_an_omitted_optional_option_falls_back_to_its_default(): void
    {
        $handler = $this->handler(PingCommand::class, 'ping');
        $interaction = $this->interaction('ping', [$this->option('name', 'Ada')]);

        $this->assertSame([$interaction, 'Ada', 1], $this->resolver()->resolve($handler, $interaction));
    }

    /**
     * An option renamed with #[Option(name: ...)] used to be keyed by the name
     * Discord knows it under, which never matched the parameter it belonged to,
     * so the handler was unreachable.
     */
    public function test_a_renamed_option_still_reaches_its_parameter(): void
    {
        $handler = $this->handler(OptionTypesCommand::class, 'option_types.renamed');
        $interaction = $this->interaction('option_types', [
            $this->option('renamed', null, [$this->option('custom', 'value')], ApplicationCommandOptionType::SUB_COMMAND),
        ]);

        $this->assertSame(['value'], $this->resolver()->resolve($handler, $interaction));
    }

    public function test_it_reads_options_nested_under_a_group_and_subcommand(): void
    {
        $handler = $this->handler(MusicCommand::class, 'music.playlist.play');
        $interaction = $this->interaction('music', [
            $this->option('playlist', null, [
                $this->option('play', null, [
                    $this->option('title', 'Bohemian Rhapsody'),
                ], ApplicationCommandOptionType::SUB_COMMAND),
            ], ApplicationCommandOptionType::SUB_COMMAND_GROUP),
        ]);

        $this->assertSame(['Bohemian Rhapsody'], $this->resolver()->resolve($handler, $interaction));
    }

    public function test_a_missing_required_option_is_reported_against_the_parameter(): void
    {
        $handler = $this->handler(MusicCommand::class, 'music.playlist.play');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: title for command "music.playlist.play"');

        $this->resolver()->resolve($handler, $this->interaction('music', []));
    }
}
