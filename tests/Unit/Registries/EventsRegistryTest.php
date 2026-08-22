<?php

namespace Tempcord\Tests\Unit\Registries;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Attributes\Event;
use Tempcord\Compiler\EventCompiler;
use Tempcord\Definitions\EventDefinition;
use Tempcord\Registries\EventsRegistry;
use Tempcord\Runtime\Outcome;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\ReadyListener;
use Tempest\Container\GenericContainer;
use Tempest\Reflection\ClassReflector;

#[CoversClass(EventsRegistry::class)]
final class EventsRegistryTest extends BaseTestCase
{
    protected function setUp(): void
    {
        ReadyListener::$received = [];
    }

    private function definition(string $class): EventDefinition
    {
        $reflector = new ClassReflector($class);

        /** @var Event $attribute */
        $attribute = $reflector->getAttribute(Event::class);

        return new EventCompiler()->compile($reflector, $attribute);
    }

    private function registry(): EventsRegistry
    {
        return new EventsRegistry(new GenericContainer());
    }

    public function test_it_reports_nothing_when_no_listeners_were_discovered(): void
    {
        $this->assertSame([], $this->registry()->listen(new FakeDiscord(new RecordingHttp())));
    }

    public function test_it_attaches_a_listener_to_the_gateway(): void
    {
        $registry = $this->registry();
        $registry->add($this->definition(ReadyListener::class));

        $discord = new FakeDiscord(new RecordingHttp());
        $outcomes = $registry->listen($discord);

        $this->assertSame(
            ['Added listener for: READY'],
            array_map(static fn(Outcome $outcome) => $outcome->message, $outcomes),
        );

        $payload = new \stdClass();
        $discord->gateway->events->emit('READY', [$payload]);

        $this->assertSame([$payload], ReadyListener::$received);
    }

    public function test_several_listeners_for_one_event_all_fire(): void
    {
        $registry = $this->registry();
        $registry->add($this->definition(ReadyListener::class));
        $registry->add($this->definition(ReadyListener::class));

        $discord = new FakeDiscord(new RecordingHttp());

        $this->assertCount(2, $registry->listen($discord));

        $discord->gateway->events->emit('READY', [new \stdClass()]);

        $this->assertCount(2, ReadyListener::$received);
    }
}
