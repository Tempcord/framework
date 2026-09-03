<?php

namespace Tempcord\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\InteractionData;
use Tempcord\Runtime\MiddlewarePipeline;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Fixtures\InnerMiddleware;
use Tempcord\Tests\Fixtures\OuterMiddleware;
use Tempcord\Tests\Fixtures\RefusingMiddleware;
use Tempcord\Tests\Fixtures\TrailMiddleware;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Container\GenericContainer;

#[CoversClass(MiddlewarePipeline::class)]
final class MiddlewarePipelineTest extends TestCase
{
    protected function setUp(): void
    {
        TrailMiddleware::$trail = [];
    }

    private function pipeline(): MiddlewarePipeline
    {
        return new MiddlewarePipeline(new GenericContainer());
    }

    private function interaction(): CommandInteraction
    {
        $interaction = new InteractionCreate();
        $interaction->id = '1';
        $interaction->token = 'token';
        $interaction->data = new InteractionData();

        return new CommandInteraction($interaction, new FakeDiscord(new RecordingHttp()));
    }

    private function record(string $what): callable
    {
        return static function () use ($what): void {
            TrailMiddleware::$trail[] = $what;
        };
    }

    public function test_a_handler_with_no_middleware_is_called_straight(): void
    {
        $this->pipeline()->run([], $this->interaction(), $this->record('handler'));

        $this->assertSame(['handler'], TrailMiddleware::$trail);
    }

    public function test_the_first_middleware_listed_is_the_outermost(): void
    {
        $this->pipeline()->run(
            [OuterMiddleware::class, InnerMiddleware::class],
            $this->interaction(),
            $this->record('handler'),
        );

        $this->assertSame(['outer', 'inner', 'handler'], TrailMiddleware::$trail);
    }

    public function test_a_middleware_that_does_not_continue_stops_the_handler(): void
    {
        $this->pipeline()->run(
            [OuterMiddleware::class, RefusingMiddleware::class, InnerMiddleware::class],
            $this->interaction(),
            $this->record('handler'),
        );

        $this->assertSame(['outer', 'refused'], TrailMiddleware::$trail);
    }

    public function test_middleware_written_as_an_object_is_used_as_it_stands(): void
    {
        $this->pipeline()->run(
            [new TrailMiddleware('inline')],
            $this->interaction(),
            $this->record('handler'),
        );

        $this->assertSame(['inline', 'handler'], TrailMiddleware::$trail);
    }

    /**
     * Nothing behind a refusal is built, which is the point of resolving each
     * one only as it is reached: middleware that reads a database or calls an
     * API costs nothing when the request never gets that far.
     */
    public function test_middleware_behind_a_refusal_is_never_constructed(): void
    {
        $built = 0;

        $container = new GenericContainer();
        $container->singleton(InnerMiddleware::class, function () use (&$built): InnerMiddleware {
            $built++;

            return new InnerMiddleware();
        });

        new MiddlewarePipeline($container)->run(
            [RefusingMiddleware::class, InnerMiddleware::class],
            $this->interaction(),
            $this->record('handler'),
        );

        $this->assertSame(0, $built);
    }
}
