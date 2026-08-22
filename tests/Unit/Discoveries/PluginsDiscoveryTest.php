<?php

namespace Tempcord\Tests\Unit\Discoveries;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;
use Tempcord\Discoveries\PluginsDiscovery;
use Tempcord\Plugins\Plugin;
use Tempcord\Registries\PluginsRegistry;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Fixtures\RecordingPlugin;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Container\GenericContainer;
use Tempest\Discovery\DiscoveryItems;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Reflection\ClassReflector;

#[CoversClass(PluginsDiscovery::class)]
final class PluginsDiscoveryTest extends TestCase
{
    private DiscoveryLocation $location;

    protected function setUp(): void
    {
        $this->location = new DiscoveryLocation(
            namespace: 'Tempcord\\Tests\\Fixtures\\',
            path: __DIR__ . '/../../Fixtures',
        );
    }

    private function discovery(PluginsRegistry $plugins): PluginsDiscovery
    {
        $discovery = new PluginsDiscovery(new GenericContainer(), $plugins);
        $discovery->setItems(new DiscoveryItems());

        return $discovery;
    }

    public function test_it_discovers_plugins(): void
    {
        $plugins = new PluginsRegistry(new NullLogger());
        $discovery = $this->discovery($plugins);

        $discovery->discover($this->location, new ClassReflector(RecordingPlugin::class));
        $discovery->apply();

        $this->assertCount(1, $plugins->all());
        $this->assertInstanceOf(Plugin::class, $plugins->all()[0]);
    }

    public function test_it_ignores_classes_that_are_not_plugins(): void
    {
        $discovery = $this->discovery(new PluginsRegistry(new NullLogger()));

        $discovery->discover($this->location, new ClassReflector(PingCommand::class));

        $this->assertCount(0, iterator_to_array($discovery->getItems()));
    }

    /**
     * The interface itself is reachable through discovery and must not be
     * treated as a plugin to instantiate.
     */
    public function test_it_ignores_the_interface_itself(): void
    {
        $discovery = $this->discovery(new PluginsRegistry(new NullLogger()));

        $discovery->discover($this->location, new ClassReflector(Plugin::class));

        $this->assertCount(0, iterator_to_array($discovery->getItems()));
    }
}
