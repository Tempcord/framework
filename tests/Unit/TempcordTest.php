<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;
use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Gateway\Events\Ready;
use Tempcord\Discord\AllCommandExtension;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\EventsRegistry;
use Tempcord\Registries\PluginsRegistry;
use Tempcord\Runtime\ArgumentResolver;
use Tempcord\Runtime\AutocompleteResponder;
use Tempcord\Runtime\ChoiceFactory;
use Tempcord\Runtime\CommandDispatcher;
use Tempcord\Runtime\CommandRegistrar;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\Runtime\Outcome;
use Tempcord\Tempcord;
use Tempcord\TempcordConfig;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\ReadyListener;
use Tempest\Container\GenericContainer;

#[CoversClass(Tempcord::class)]
final class TempcordTest extends TestCase
{
    private FakeDiscord $discord;
    private CommandsRegistry $commands;
    private EventsRegistry $events;
    private PluginsRegistry $plugins;

    protected function setUp(): void
    {
        ReadyListener::$received = [];

        $this->discord = new FakeDiscord(new RecordingHttp());
        $this->commands = new CommandsRegistry(
            extension: new AllCommandExtension(),
            registrar: new CommandRegistrar(
                new CommandBuilderFactory(),
                new TempcordConfig('::token::', new Bitwise()),
                new NullLogger(),
                new RecordingHttp(),
            ),
            dispatcher: new CommandDispatcher(
                new ArgumentResolver(new OptionValueResolver($this->discord)),
                new GenericContainer(),
                new NullLogger(),
            ),
            autocomplete: new AutocompleteResponder(new ChoiceFactory()),
        );
        $this->events = new EventsRegistry(new GenericContainer());
        $this->plugins = new PluginsRegistry(new NullLogger());
    }

    private function tempcord(): Tempcord
    {
        return new Tempcord($this->discord, $this->commands, $this->events, $this->plugins);
    }

    public function test_it_registers_the_command_extension_once_the_gateway_is_ready(): void
    {
        $tempcord = $this->tempcord();

        $this->assertFalse($tempcord->booted);

        $this->discord->gateway->events->emit(Events::READY, [new Ready()]);

        $this->assertTrue($tempcord->booted);
    }

    /**
     * Listening covers both registries, so a bot with commands and events gets
     * one report describing everything that was wired up.
     */
    public function test_listening_reports_commands_and_events_together(): void
    {
        $this->commands->add($this->definition(ModerationCommand::class));

        $messages = array_map(
            static fn(Outcome $outcome) => $outcome->message,
            $this->tempcord()->listen(),
        );

        $this->assertSame(['Command "moderation.kick" listened.'], $messages);
    }
}
