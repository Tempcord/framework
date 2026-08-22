<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Tempcord\AllCommandExtension;
use Tempcord\Discoveries\CommandsDiscovery;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\NoHandlerCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempest\Discovery\DiscoveryItems;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Reflection\ClassReflector;

#[CoversClass(CommandsDiscovery::class)]
final class DiscoveryTest extends TestCase
{
    private DiscoveryLocation $location;

    protected function setUp(): void
    {
        $this->location = new DiscoveryLocation(
            namespace: 'Tempcord\\Tests\\Fixtures\\',
            path: __DIR__ . '/../Fixtures',
        );
    }

    private function discovery(CommandsRegistry $registry): CommandsDiscovery
    {
        $discovery = new CommandsDiscovery($registry);
        $discovery->setItems(new DiscoveryItems());

        return $discovery;
    }

    public function test_it_discovers_command_classes_and_feeds_them_to_the_registry(): void
    {
        $registry = new CommandsRegistry(new AllCommandExtension());
        $discovery = $this->discovery($registry);

        $discovery->discover($this->location, new ClassReflector(PingCommand::class));
        $discovery->discover($this->location, new ClassReflector(ModerationCommand::class));
        $discovery->apply();

        $stored = new ReflectionProperty(CommandsRegistry::class, 'commands')->getValue($registry);

        $this->assertSame(['ping', 'moderation'], array_keys($stored));
    }

    public function test_it_attaches_the_class_reflector_to_the_discovered_attribute(): void
    {
        $discovery = $this->discovery(new CommandsRegistry(new AllCommandExtension()));
        $discovery->discover($this->location, new ClassReflector(PingCommand::class));

        $items = iterator_to_array($discovery->getItems());

        $this->assertCount(1, $items);
        $this->assertSame(PingCommand::class, $items[0]->reflector->getName());
    }

    public function test_it_ignores_classes_without_the_command_attribute(): void
    {
        $discovery = $this->discovery(new CommandsRegistry(new AllCommandExtension()));
        $discovery->discover($this->location, new ClassReflector(NoHandlerCommand::class));
        $discovery->discover($this->location, new ClassReflector(DiscoveryTest::class));

        $this->assertCount(1, iterator_to_array($discovery->getItems()));
    }
}
