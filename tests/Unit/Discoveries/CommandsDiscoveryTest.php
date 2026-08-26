<?php

namespace Tempcord\Tests\Unit\Discoveries;

use PHPUnit\Framework\Attributes\CoversClass;
use CyberWolf\Discord\Bitwise\Bitwise;
use ReflectionProperty;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Definitions\CommandDefinition;
use Tempcord\Discord\AllCommandExtension;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Discoveries\CommandsDiscovery;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Runtime\ArgumentResolver;
use Tempcord\Runtime\AutocompleteResponder;
use Tempcord\Runtime\ChoiceFactory;
use Tempcord\Runtime\CommandDispatcher;
use Tempcord\Runtime\CommandRegistrar;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\TempcordConfig;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Container\GenericContainer;
use Tempest\Discovery\DiscoveryItems;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Reflection\ClassReflector;

#[CoversClass(CommandsDiscovery::class)]
final class CommandsDiscoveryTest extends TestCase
{
    private DiscoveryLocation $location;

    protected function setUp(): void
    {
        $this->location = new DiscoveryLocation(
            namespace: 'Tempcord\\Tests\\Fixtures\\',
            path: __DIR__ . '/../../Fixtures',
        );
    }

    private function registry(): CommandsRegistry
    {
        $discord = new FakeDiscord(new RecordingHttp());

        return new CommandsRegistry();
    }

    private function discovery(CommandsRegistry $registry): CommandsDiscovery
    {
        $discovery = new CommandsDiscovery($registry, new CommandCompiler());
        $discovery->setItems(new DiscoveryItems());

        return $discovery;
    }

    public function test_it_discovers_command_classes_and_feeds_them_to_the_registry(): void
    {
        $registry = $this->registry();
        $discovery = $this->discovery($registry);

        $discovery->discover($this->location, new ClassReflector(PingCommand::class));
        $discovery->discover($this->location, new ClassReflector(ModerationCommand::class));
        $discovery->apply();

        $stored = new ReflectionProperty(CommandsRegistry::class, 'commands')->getValue($registry);

        $this->assertSame(['ping', 'moderation'], array_keys($stored));
    }

    /**
     * Discovery hands the registry finished definitions, not attributes that
     * still need a reflector attached before they can be read.
     */
    public function test_it_stores_compiled_definitions(): void
    {
        $discovery = $this->discovery($this->registry());
        $discovery->discover($this->location, new ClassReflector(PingCommand::class));

        $items = iterator_to_array($discovery->getItems());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(CommandDefinition::class, $items[0]);
        $this->assertSame('ping', $items[0]->name);
    }

    public function test_it_ignores_classes_without_the_command_attribute(): void
    {
        $discovery = $this->discovery($this->registry());
        $discovery->discover($this->location, new ClassReflector(CommandsDiscoveryTest::class));

        $this->assertCount(0, iterator_to_array($discovery->getItems()));
    }
}
