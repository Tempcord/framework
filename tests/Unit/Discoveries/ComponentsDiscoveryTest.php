<?php

namespace Tempcord\Tests\Unit\Discoveries;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Definitions\ComponentDefinition;
use Tempcord\Discoveries\ComponentsDiscovery;
use Tempcord\Enums\ComponentKind;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Fixtures\ReportButton;
use Tempcord\Tests\Fixtures\TournamentButtons;
use Tempest\Discovery\DiscoveryItems;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Reflection\ClassReflector;

#[CoversClass(ComponentsDiscovery::class)]
final class ComponentsDiscoveryTest extends BaseTestCase
{
    private DiscoveryLocation $location;

    protected function setUp(): void
    {
        $this->location = new DiscoveryLocation(
            namespace: 'Tempcord\\Tests\\Fixtures\\',
            path: __DIR__ . '/../../Fixtures',
        );
    }

    private function discovery(ComponentsRegistry $registry): ComponentsDiscovery
    {
        $discovery = new ComponentsDiscovery($registry);
        $discovery->setItems(new DiscoveryItems());

        return $discovery;
    }

    public function test_it_feeds_discovered_handlers_to_the_registry(): void
    {
        $registry = new ComponentsRegistry();
        $discovery = $this->discovery($registry);

        $discovery->discover($this->location, new ClassReflector(ReportButton::class));
        $discovery->discover($this->location, new ClassReflector(TournamentButtons::class));
        $discovery->apply();

        $this->assertSame(4, $registry->count());
        $this->assertNotNull($registry->match(ComponentKind::Button, 'report'));
        $this->assertNotNull($registry->match(ComponentKind::Button, 'tournament.drop.1'));
    }

    public function test_it_stores_compiled_definitions(): void
    {
        $discovery = $this->discovery(new ComponentsRegistry());
        $discovery->discover($this->location, new ClassReflector(ReportButton::class));

        $items = iterator_to_array($discovery->getItems());

        $this->assertCount(1, $items);
        $this->assertInstanceOf(ComponentDefinition::class, $items[0]);
        $this->assertSame('report', $items[0]->customId->pattern);
    }

    public function test_it_ignores_classes_without_component_attributes(): void
    {
        $discovery = $this->discovery(new ComponentsRegistry());
        $discovery->discover($this->location, new ClassReflector(PingCommand::class));

        $this->assertCount(0, iterator_to_array($discovery->getItems()));
    }
}
