<?php

namespace Tempcord\Tests\Unit\Plugins;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Registries\PluginsRegistry;
use Tempcord\Runtime\Outcome;
use Tempcord\Runtime\OutcomeLevel;
use Tempcord\Runtime\PluginBooter;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempcord\Tests\Fixtures\RecordingPlugin;
use Tempcord\Tests\Fixtures\ThrowingPlugin;
use Tempcord\Tests\Unit\TestCase;

#[CoversClass(PluginsRegistry::class)]
#[CoversClass(PluginBooter::class)]
final class PluginsRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        RecordingPlugin::$booted = [];
    }

    /** @return list<string> */
    private function messages(array $outcomes): array
    {
        return array_map(static fn(Outcome $outcome) => $outcome->message, $outcomes);
    }

    public function test_a_plugin_is_booted_with_the_bot(): void
    {
        $plugins = new PluginsRegistry();
        $plugins->add(new RecordingPlugin());

        $tempcord = $this->tempcord(plugins: $plugins);
        $outcomes = new PluginBooter(new RecordingLogger())->bootAll($plugins->all(), $tempcord);

        $this->assertSame([$tempcord], RecordingPlugin::$booted);
        $this->assertSame(['Plugin "RecordingPlugin" booted.'], $this->messages($outcomes));
    }

    /**
     * Discovery can reach the same class through more than one location, and a
     * plugin booting twice would register its extensions twice.
     */
    public function test_the_same_plugin_added_twice_boots_once(): void
    {
        $plugins = new PluginsRegistry();
        $plugins->add(new RecordingPlugin());
        $plugins->add(new RecordingPlugin());

        new PluginBooter(new RecordingLogger())->bootAll($plugins->all(), $this->tempcord(plugins: $plugins));

        $this->assertCount(1, $plugins->all());
        $this->assertCount(1, RecordingPlugin::$booted);
    }

    /**
     * One plugin that cannot start must not stop the bot, or the others.
     */
    public function test_a_plugin_that_throws_is_reported_and_the_rest_still_boot(): void
    {
        $logger = new RecordingLogger();
        $plugins = new PluginsRegistry();
        $plugins->add(new ThrowingPlugin());
        $plugins->add(new RecordingPlugin());

        $outcomes = new PluginBooter($logger)->bootAll($plugins->all(), $this->tempcord(plugins: $plugins));

        $this->assertSame(
            [OutcomeLevel::Error, OutcomeLevel::Success],
            array_map(static fn(Outcome $outcome) => $outcome->level, $outcomes),
        );
        $this->assertCount(1, RecordingPlugin::$booted);
        $this->assertStringContainsString('could not reach the scheduler', $logger->messages[0]);
    }

    public function test_no_plugins_reports_nothing(): void
    {
        $this->assertSame(
            [],
            new PluginBooter(new RecordingLogger())->bootAll([], $this->tempcord()),
        );
    }

    /**
     * Plugins boot as part of listening, after commands and events are bound
     * and before the gateway opens.
     */
    public function test_plugins_boot_as_part_of_listening(): void
    {
        $plugins = new PluginsRegistry();
        $plugins->add(new RecordingPlugin());

        $tempcord = $this->tempcord(plugins: $plugins);
        $messages = $this->messages($tempcord->listen());

        $this->assertSame('Plugin "RecordingPlugin" booted.', end($messages));
        $this->assertSame([$tempcord], RecordingPlugin::$booted);
    }

    /**
     * The registry is storage and nothing else, so that discovery can build it
     * before the container has the services the runtime needs.
     */
    public function test_it_needs_nothing_to_construct(): void
    {
        $this->assertSame(0, new PluginsRegistry()->count());
    }
}
