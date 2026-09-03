<?php

namespace Tempcord\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Discord\Enums\ApplicationCommandOptionType;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\ApplicationCommandInteractionDataOptionStructure;
use Tempcord\Discord\Parts\InteractionData;
use Tempcord\Definitions\HandlerDefinition;
use Tempcord\Runtime\ArgumentResolver;
use Tempcord\Runtime\CommandDispatcher;
use Tempcord\Runtime\MiddlewarePipeline;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempcord\Tests\Fixtures\GuardedCommand;
use Tempcord\Tests\Fixtures\RecordingCommand;
use Tempcord\Tests\Fixtures\TrailMiddleware;
use Tempcord\Tests\Fixtures\ThrowingCommand;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Container\GenericContainer;

#[CoversClass(CommandDispatcher::class)]
final class CommandDispatcherTest extends TestCase
{
    private RecordingLogger $logger;

    protected function setUp(): void
    {
        RecordingCommand::$calls = [];
        GuardedCommand::$calls = [];
        TrailMiddleware::$trail = [];
        $this->logger = new RecordingLogger();
    }

    private function dispatcher(): CommandDispatcher
    {
        return new CommandDispatcher(
            new ArgumentResolver(new OptionValueResolver(new FakeDiscord(new RecordingHttp()))),
            new GenericContainer(),
            $this->logger,
            new MiddlewarePipeline(new GenericContainer()),
        );
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

        return new CommandInteraction($interaction, new FakeDiscord(new RecordingHttp()));
    }

    private function option(string $name, mixed $value): ApplicationCommandInteractionDataOptionStructure
    {
        $option = new ApplicationCommandInteractionDataOptionStructure();
        $option->name = $name;
        $option->type = ApplicationCommandOptionType::STRING;
        $option->value = $value;
        $option->options = [];

        return $option;
    }

    private function handler(string $class, string $path): HandlerDefinition
    {
        return $this->definition($class)->handlers[$path];
    }

    public function test_it_invokes_the_command_with_its_resolved_arguments(): void
    {
        $this->dispatcher()->dispatch(
            $this->handler(RecordingCommand::class, 'recording'),
            $this->interaction('recording', [$this->option('subject', 'world')]),
        );

        $this->assertSame(['world'], RecordingCommand::$calls);
        $this->assertSame([], $this->logger->messages);
    }

    public function test_it_runs_the_middleware_declared_around_a_handler(): void
    {
        $this->dispatcher()->dispatch(
            $this->handler(GuardedCommand::class, 'guarded.shut'),
            $this->interaction('guarded', []),
        );

        $this->assertSame(['outer', 'inner'], TrailMiddleware::$trail);
        $this->assertSame(['shut'], GuardedCommand::$calls);
    }

    /**
     * A command that throws must be logged rather than allowed to take the
     * gateway connection down with it.
     */
    public function test_a_throwing_command_is_logged_and_contained(): void
    {
        $this->dispatcher()->dispatch(
            $this->handler(ThrowingCommand::class, 'throwing'),
            $this->interaction('throwing', []),
        );

        $this->assertCount(1, $this->logger->messages);
        $this->assertStringContainsString('Command "throwing" failed: nope', $this->logger->messages[0]);
    }
}
