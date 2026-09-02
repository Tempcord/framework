<?php

namespace Tempcord\Registries;

use React\EventLoop\LoopInterface;
use Tempcord\Definitions\ScheduledTaskDefinition;
use Tempcord\Runtime\Outcome;
use Tempcord\Runtime\TaskRunner;
use Tempest\Container\Container;
use Tempest\Container\Singleton;

/**
 * Holds every discovered scheduled task and puts it on the event loop.
 */
#[Singleton]
final class ScheduledTasksRegistry
{
    /** @var list<ScheduledTaskDefinition> */
    private array $tasks = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    public function add(ScheduledTaskDefinition $task): void
    {
        $this->tasks[] = $task;
    }

    /**
     * @return list<ScheduledTaskDefinition>
     */
    public function all(): array
    {
        return $this->tasks;
    }

    /**
     * Timers do not fire until the loop runs, which is after the gateway opens,
     * so this only has to happen before then.
     *
     * @return list<Outcome>
     */
    public function start(LoopInterface $loop): array
    {
        if ($this->tasks === []) {
            return [];
        }

        /*
         * Resolved here rather than in the constructor: discovery builds this
         * registry while the container is still being assembled, before the
         * initializers that provide the logger have themselves been found.
         */
        $runner = $this->container->get(TaskRunner::class);
        $outcomes = [];

        foreach ($this->tasks as $task) {
            $loop->addPeriodicTimer(
                $task->everySeconds,
                static fn() => $runner->run($task),
            );

            $outcomes[] = Outcome::success(
                'Scheduled ' . $task->task . ' every ' . $task->everySeconds . 's.',
            );
        }

        return $outcomes;
    }
}
