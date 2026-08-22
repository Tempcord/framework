<?php

namespace Tempcord\Tests\Unit\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;
use Tempcord\Attributes\Event;
use Tempcord\Compiler\EventCompiler;
use Tempcord\Tests\Fixtures\HandlerlessListener;
use Tempcord\Tests\Fixtures\ReadyListener;
use Tempest\Reflection\ClassReflector;

#[CoversClass(EventCompiler::class)]
final class EventCompilerTest extends BaseTestCase
{
    private function compile(string $class): object
    {
        $reflector = new ClassReflector($class);

        /** @var Event $attribute */
        $attribute = $reflector->getAttribute(Event::class);

        return new EventCompiler()->compile($reflector, $attribute);
    }

    public function test_it_pairs_the_event_name_with_the_listener(): void
    {
        $definition = $this->compile(ReadyListener::class);

        $this->assertSame('READY', $definition->name);
        $this->assertSame(ReadyListener::class, $definition->listener);
    }

    public function test_a_listener_without_invoke_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('should declare an __invoke method');

        $this->compile(HandlerlessListener::class);
    }
}
