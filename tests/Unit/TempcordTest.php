<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Discord\Bitwise\Bitwise;
use Tempcord\Discord\Constants\Events;
use Tempcord\Discord\Gateway\Events\Ready;
use Tempcord\Cache\Cache;
use Tempcord\Discord\AllCommandExtension;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Discord\ComponentExtension;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\ComponentsRegistry;
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
use Tempcord\Compiler\ComponentCompiler;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\ReadyListener;
use Tempcord\Tests\Fixtures\ReportButton;
use Tempest\Reflection\ClassReflector;
use Tempest\Container\GenericContainer;
use Tempest\Log\Logger;

#[CoversClass(Tempcord::class)]
final class TempcordTest extends TestCase
{
    private FakeDiscord $discord;
    private CommandsRegistry $commands;
    private ComponentsRegistry $components;
    private EventsRegistry $events;
    private PluginsRegistry $plugins;

    protected function setUp(): void
    {
        ReadyListener::$received = [];

        $this->discord = new FakeDiscord(new RecordingHttp());
        $this->commands = new CommandsRegistry();
        $this->components = new ComponentsRegistry();
        $container = new GenericContainer();
        $container->singleton(Logger::class, new RecordingLogger());

        $this->events = new EventsRegistry($container);
        $this->plugins = new PluginsRegistry();
    }


    private function bot(): Tempcord
    {
        return $this->tempcord(
            discord: $this->discord,
            commands: $this->commands,
            components: $this->components,
            events: $this->events,
            plugins: $this->plugins,
        );
    }

    public function test_it_registers_its_extensions_once_the_gateway_is_ready(): void
    {
        $tempcord = $this->bot();

        $this->assertFalse($tempcord->booted);
        $this->assertFalse($this->discord->hasExtension(AllCommandExtension::class));

        $this->discord->gateway->events->emit(Events::READY, [new Ready()]);

        $this->assertTrue($tempcord->booted);
        $this->assertTrue($this->discord->hasExtension(AllCommandExtension::class));
        $this->assertTrue($this->discord->hasExtension(ComponentExtension::class));
    }

    /**
     * Listening covers both registries, so a bot with commands and events gets
     * one report describing everything that was wired up.
     */
    public function test_listening_reports_commands_components_and_events_together(): void
    {
        $this->commands->add($this->definition(ModerationCommand::class));

        foreach (new ComponentCompiler()->compile(new ClassReflector(ReportButton::class)) as $component) {
            $this->components->add($component);
        }

        $messages = array_map(
            static fn(Outcome $outcome) => $outcome->message,
            $this->bot()->listen(),
        );

        $this->assertSame(
            ['Command "moderation.kick" listened.', 'Listening button "report".'],
            $messages,
        );
    }

    /**
     * The cache subscribes before any listener the bot declares, so a handler
     * reading it sees the state the event it is handling has already produced.
     */
    public function test_the_cache_subscribes_ahead_of_everything_else(): void
    {
        $cache = new Cache();

        $messages = array_map(
            static fn(Outcome $outcome) => $outcome->message,
            $this->tempcord(
                discord: $this->discord,
                commands: $this->commands,
                components: $this->components,
                events: $this->events,
                plugins: $this->plugins,
                cache: $cache,
            )->listen(),
        );

        $this->assertSame(
            'Caching guilds, channels, roles, members and voice states.',
            $messages[0] ?? null,
        );

        $guild = new \Tempcord\Discord\Gateway\Events\GuildCreate();
        $guild->id = 'g1';
        $guild->name = 'Guild';
        $this->discord->gateway->events->emit(Events::GUILD_CREATE, [$guild]);

        $this->assertNotNull($cache->guild('g1'));
    }

    public function test_a_bot_configured_without_a_cache_wires_none(): void
    {
        $messages = array_map(
            static fn(Outcome $outcome) => $outcome->message,
            $this->tempcord(
                discord: $this->discord,
                commands: $this->commands,
                components: $this->components,
                events: $this->events,
                plugins: $this->plugins,
            )->listen(),
        );

        $this->assertNotContains(
            'Caching guilds, channels, roles, members and voice states.',
            $messages,
        );
    }
}
