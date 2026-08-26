<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use CyberWolf\Discord\Bitwise\Bitwise;
use CyberWolf\Discord\Constants\Events;
use CyberWolf\Discord\Gateway\Events\Ready;
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
use Tempcord\Tests\Doubles\RecordingLogger;
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
        $this->commands = new CommandsRegistry();
        $this->events = new EventsRegistry(new GenericContainer());
        $this->plugins = new PluginsRegistry();
    }


    public function test_it_registers_the_command_extension_once_the_gateway_is_ready(): void
    {
        $tempcord = $this->tempcord(discord: $this->discord, commands: $this->commands, events: $this->events, plugins: $this->plugins);

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
            $this->tempcord(discord: $this->discord, commands: $this->commands, events: $this->events, plugins: $this->plugins)->listen(),
        );

        $this->assertSame(['Command "moderation.kick" listened.'], $messages);
    }
}
