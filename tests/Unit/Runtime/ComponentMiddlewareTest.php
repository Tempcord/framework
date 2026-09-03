<?php

namespace Tempcord\Tests\Unit\Runtime;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Compiler\ComponentCompiler;
use Tempcord\Discord\ComponentExtension;
use Tempcord\Discord\Constants\Events;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Runtime\ComponentArgumentResolver;
use Tempcord\Runtime\ComponentBinder;
use Tempcord\Runtime\ComponentDispatcher;
use Tempcord\Runtime\MiddlewarePipeline;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\Interactions;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempcord\Tests\Fixtures\GuardedButtons;
use Tempcord\Tests\Fixtures\NotMiddlewareButton;
use Tempcord\Tests\Fixtures\RecordingInteractionMiddleware;
use Tempcord\Tests\Fixtures\TrailMiddleware;
use Tempest\Container\GenericContainer;
use Tempest\Reflection\ClassReflector;

/**
 * Middleware around a component, end to end: an interaction pushed onto the
 * gateway runs the chain before the handler, and a refusal means the handler
 * never runs at all.
 *
 * The same guard belongs on a subcommand and on the button that does the same
 * thing, so this is the command path's contract answered for components.
 */
#[CoversClass(ComponentDispatcher::class)]
#[CoversClass(ComponentCompiler::class)]
final class ComponentMiddlewareTest extends BaseTestCase
{
    private FakeDiscord $discord;

    private ComponentsRegistry $registry;

    private ComponentBinder $binder;

    protected function setUp(): void
    {
        GuardedButtons::$calls = [];
        TrailMiddleware::$trail = [];
        RecordingInteractionMiddleware::$seen = [];

        $this->discord = new FakeDiscord(new RecordingHttp());
        $this->registry = new ComponentsRegistry();

        $extension = new ComponentExtension($this->registry);
        $extension->initialize($this->discord);

        $this->binder = new ComponentBinder(
            $extension,
            new ComponentDispatcher(
                new ComponentArgumentResolver($this->discord),
                new GenericContainer(),
                new RecordingLogger(),
                new MiddlewarePipeline(new GenericContainer()),
                $this->discord,
            ),
        );
    }

    private function bind(string $class): void
    {
        foreach (new ComponentCompiler()->compile(new ClassReflector($class)) as $definition) {
            $this->registry->add($definition);
        }

        $this->binder->bindAll($this->registry->all());
    }

    private function arrive(InteractionCreate $interaction): void
    {
        $this->discord->gateway->events->emit(Events::INTERACTION_CREATE, [$interaction]);
    }

    public function test_the_first_middleware_listed_is_the_outermost(): void
    {
        $this->bind(GuardedButtons::class);

        $this->arrive(Interactions::button('guarded.press'));

        $this->assertSame(['outer', 'inner'], TrailMiddleware::$trail);
        $this->assertSame(['press'], GuardedButtons::$calls);
    }

    public function test_a_middleware_that_does_not_continue_stops_the_handler(): void
    {
        $this->bind(GuardedButtons::class);

        $this->arrive(Interactions::button('guarded.refused'));

        $this->assertSame(['refused'], TrailMiddleware::$trail);
        $this->assertSame([], GuardedButtons::$calls);
    }

    public function test_a_component_declaring_none_still_runs(): void
    {
        $this->bind(GuardedButtons::class);

        $this->arrive(Interactions::button('guarded.open'));

        $this->assertSame([], TrailMiddleware::$trail);
        $this->assertSame(['open'], GuardedButtons::$calls);
    }

    /**
     * Each kind of component is answered with its own shape of interaction, and
     * the middleware is handed that shape whether or not the handler it guards
     * ever asked for one — a guard has to be able to say no.
     */
    public function test_the_middleware_is_handed_the_interaction_for_that_kind(): void
    {
        $this->bind(GuardedButtons::class);

        $this->arrive(Interactions::button('guarded.blind.alpha'));
        $this->arrive(Interactions::selectMenu('guarded.pick', ['one']));
        $this->arrive(Interactions::modal('guarded.submit'));

        $this->assertInstanceOf(ButtonInteraction::class, RecordingInteractionMiddleware::$seen[0]);
        $this->assertInstanceOf(ComponentInteraction::class, RecordingInteractionMiddleware::$seen[1]);
        $this->assertInstanceOf(ModalSubmitInteraction::class, RecordingInteractionMiddleware::$seen[2]);

        $this->assertSame(['blind:alpha', 'pick', 'submit'], GuardedButtons::$calls);
    }

    public function test_a_class_that_is_not_middleware_is_refused(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('does not implement');

        new ComponentCompiler()->compile(new ClassReflector(NotMiddlewareButton::class));
    }
}
