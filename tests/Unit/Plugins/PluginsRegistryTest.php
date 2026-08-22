<?php

namespace Tempcord\Tests\Unit\Plugins;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;
use Ragnarok\Fenrir\Bitwise\Bitwise;
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
use Tempcord\Runtime\OutcomeLevel;
use Tempcord\Tempcord;
use Tempcord\TempcordConfig;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempcord\Tests\Fixtures\RecordingPlugin;
use Tempcord\Tests\Fixtures\ThrowingPlugin;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Container\GenericContainer;

#[CoversClass(PluginsRegistry::class)]
final class PluginsRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        RecordingPlugin::$booted = [];
    }

    private function tempcord(PluginsRegistry $plugins): Tempcord
    {
        $discord = new FakeDiscord(new RecordingHttp());

        return new Tempcord(
            $discord,
            new CommandsRegistry(
                extension: new AllCommandExtension(),
                registrar: new CommandRegistrar(
                    new CommandBuilderFactory(),
                    new TempcordConfig('::token::', new Bitwise()),
                    new NullLogger(),
                    new RecordingHttp(),
                ),
                dispatcher: new CommandDispatcher(
                    new ArgumentResolver(new OptionValueResolver($discord)),
                    new GenericContainer(),
                    new NullLogger(),
                ),
                autocomplete: new AutocompleteResponder(new ChoiceFactory()),
            ),
            new EventsRegistry(new GenericContainer()),
            $plugins,
        );
    }

    /** @return list<string> */
    private function messages(array $outcomes): array
    {
        return array_map(static fn(Outcome $outcome) => $outcome->message, $outcomes);
    }

    public function test_a_plugin_is_booted_with_the_bot(): void
    {
        $plugins = new PluginsRegistry(new NullLogger());
        $plugins->add(new RecordingPlugin());

        $tempcord = $this->tempcord($plugins);
        $outcomes = $plugins->boot($tempcord);

        $this->assertSame([$tempcord], RecordingPlugin::$booted);
        $this->assertSame(['Plugin "RecordingPlugin" booted.'], $this->messages($outcomes));
    }

    /**
     * Discovery can reach the same class through more than one location, and a
     * plugin booting twice would register its extensions twice.
     */
    public function test_the_same_plugin_added_twice_boots_once(): void
    {
        $plugins = new PluginsRegistry(new NullLogger());
        $plugins->add(new RecordingPlugin());
        $plugins->add(new RecordingPlugin());

        $plugins->boot($this->tempcord($plugins));

        $this->assertCount(1, $plugins->all());
        $this->assertCount(1, RecordingPlugin::$booted);
    }

    /**
     * One plugin that cannot start must not stop the bot, or the others.
     */
    public function test_a_plugin_that_throws_is_reported_and_the_rest_still_boot(): void
    {
        $logger = new RecordingLogger();
        $plugins = new PluginsRegistry($logger);
        $plugins->add(new ThrowingPlugin());
        $plugins->add(new RecordingPlugin());

        $outcomes = $plugins->boot($this->tempcord($plugins));

        $this->assertSame(
            [OutcomeLevel::Error, OutcomeLevel::Success],
            array_map(static fn(Outcome $outcome) => $outcome->level, $outcomes),
        );
        $this->assertCount(1, RecordingPlugin::$booted);
        $this->assertStringContainsString('could not reach the scheduler', $logger->messages[0]);
    }

    public function test_no_plugins_reports_nothing(): void
    {
        $plugins = new PluginsRegistry(new NullLogger());

        $this->assertSame([], $plugins->boot($this->tempcord($plugins)));
    }

    /**
     * Plugins boot as part of listening, after commands and events are bound
     * and before the gateway opens.
     */
    public function test_plugins_boot_as_part_of_listening(): void
    {
        $plugins = new PluginsRegistry(new NullLogger());
        $plugins->add(new RecordingPlugin());

        $tempcord = $this->tempcord($plugins);
        $messages = $this->messages($tempcord->listen());

        $this->assertSame('Plugin "RecordingPlugin" booted.', end($messages));
        $this->assertSame([$tempcord], RecordingPlugin::$booted);
    }
}
