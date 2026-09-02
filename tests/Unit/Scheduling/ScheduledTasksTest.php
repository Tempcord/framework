<?php

namespace Tempcord\Tests\Unit\Scheduling;

use PHPUnit\Framework\Attributes\CoversClass;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Promise\Deferred;
use RuntimeException;
use Tempcord\Attributes\Scheduled;
use Tempcord\Compiler\ScheduledTaskCompiler;
use Tempcord\Definitions\ScheduledTaskDefinition;
use Tempcord\Registries\ScheduledTasksRegistry;
use Tempcord\Runtime\TaskRunner;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempcord\Tests\Fixtures\FailingTask;
use Tempcord\Tests\Fixtures\HandlerlessTask;
use Tempcord\Tests\Fixtures\SlowTask;
use Tempcord\Tests\Fixtures\SweepTask;
use Tempcord\Tests\Fixtures\UnscheduledTask;
use Tempcord\Tests\Unit\TestCase;
use Tempest\Container\GenericContainer;
use Tempest\Log\Logger;
use Tempest\Reflection\ClassReflector;

/**
 * Work the bot does on a timer, whether or not anyone is interacting with it.
 */
#[CoversClass(ScheduledTaskCompiler::class)]
#[CoversClass(ScheduledTasksRegistry::class)]
#[CoversClass(TaskRunner::class)]
final class ScheduledTasksTest extends TestCase
{
    private RecordingLogger $logger;

    private GenericContainer $container;

    protected function setUp(): void
    {
        SweepTask::$turns = 0;
        SlowTask::$started = 0;
        SlowTask::$holding = new Deferred();

        $this->logger = new RecordingLogger();
        $this->container = new GenericContainer();
        $this->container->singleton(Logger::class, $this->logger);
    }

    private function compile(string $class): ScheduledTaskDefinition
    {
        $reflector = new ClassReflector($class);

        /** @var Scheduled $attribute */
        $attribute = $reflector->getAttribute(Scheduled::class);

        return new ScheduledTaskCompiler()->compile($reflector, $attribute);
    }

    private function registry(string ...$classes): ScheduledTasksRegistry
    {
        $registry = new ScheduledTasksRegistry($this->container);

        foreach ($classes as $class) {
            $registry->add($this->compile($class));
        }

        return $registry;
    }

    /**
     * Runs the loop for long enough to see a few turns, then stops it whether
     * or not anything happened, so a broken timer fails rather than hangs.
     */
    private function runFor(LoopInterface $loop, float $seconds): void
    {
        $loop->addTimer($seconds, static fn() => $loop->stop());
        $loop->run();
    }

    public function test_a_task_is_compiled_with_its_interval(): void
    {
        $definition = $this->compile(SweepTask::class);

        $this->assertSame(SweepTask::class, $definition->task);
        $this->assertSame(0.01, $definition->everySeconds);
    }

    public function test_a_task_without_an_invoke_method_is_refused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('should declare an __invoke method');

        $this->compile(HandlerlessTask::class);
    }

    /**
     * A zero interval asks the loop to run the task as fast as it can, which
     * starves the gateway heartbeat and drops the connection.
     */
    public function test_a_task_with_no_interval_is_refused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('greater than zero');

        $this->compile(UnscheduledTask::class);
    }

    public function test_a_scheduled_task_takes_turns_on_the_loop(): void
    {
        $loop = new StreamSelectLoop();
        $this->registry(SweepTask::class)->start($loop);

        $this->runFor($loop, 0.05);

        $this->assertGreaterThan(1, SweepTask::$turns);
    }

    /**
     * The first turn comes after the interval: a task is a repeating chore, and
     * something that must happen at startup belongs in a plugin's boot.
     */
    public function test_a_task_does_not_take_a_turn_before_its_first_interval(): void
    {
        $loop = new StreamSelectLoop();
        $this->registry(SweepTask::class)->start($loop);

        $this->assertSame(0, SweepTask::$turns);
    }

    public function test_starting_reports_what_was_scheduled(): void
    {
        $outcomes = $this->registry(SweepTask::class)->start(new StreamSelectLoop());

        $this->assertCount(1, $outcomes);
        $this->assertStringContainsString(SweepTask::class, $outcomes[0]->message);
    }

    public function test_a_bot_with_nothing_scheduled_reports_nothing(): void
    {
        $this->assertSame([], $this->registry()->start(new StreamSelectLoop()));
    }

    /**
     * A timer fires again whether or not the last turn threw. Without
     * containment the exception travels into the event loop and takes the
     * process with it.
     */
    public function test_a_task_that_throws_is_reported_and_keeps_its_place(): void
    {
        $loop = new StreamSelectLoop();
        $this->registry(FailingTask::class, SweepTask::class)->start($loop);

        $this->runFor($loop, 0.05);

        $this->assertGreaterThan(1, SweepTask::$turns);
        $this->assertNotSame([], array_filter(
            $this->logger->messages,
            static fn(string $message) => str_contains($message, 'the database went away'),
        ));
    }

    /**
     * A task slower than its own interval must not be started alongside itself,
     * or each turn makes the next one slower until nothing else gets a look in.
     */
    public function test_a_task_still_busy_from_its_last_turn_is_skipped(): void
    {
        $loop = new StreamSelectLoop();
        $this->registry(SlowTask::class)->start($loop);

        $this->runFor($loop, 0.05);

        $this->assertSame(1, SlowTask::$started);
        $this->assertNotSame([], array_filter(
            $this->logger->messages,
            static fn(string $message) => str_contains($message, 'still busy'),
        ));
    }

    /**
     * Once it finishes, the task goes back to taking its turns.
     */
    public function test_a_task_that_catches_up_is_scheduled_again(): void
    {
        $loop = new StreamSelectLoop();
        $this->registry(SlowTask::class)->start($loop);

        $this->runFor($loop, 0.03);
        SlowTask::$holding->resolve(null);
        $this->runFor($loop, 0.03);

        $this->assertGreaterThan(1, SlowTask::$started);
    }
}
