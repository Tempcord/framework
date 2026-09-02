<?php

namespace Tempcord\Runtime;

use Tempcord\Definitions\ScheduledTaskDefinition;
use Tempest\Container\Container;
use Tempest\Log\Logger;
use Throwable;

use function React\Async\async;

/**
 * Takes one turn of a scheduled task.
 *
 * A timer is unforgiving in a way an event listener is not: it fires again
 * whether or not the last turn finished or threw, forever. Both are contained
 * here so that neither can quietly stop the bot doing its other work.
 */
final class TaskRunner
{
    /** @var array<string, true> tasks whose previous turn has not finished */
    private array $running = [];

    public function __construct(
        private readonly Container $container,
        private readonly Logger $logger,
    ) {}

    public function run(ScheduledTaskDefinition $task): void
    {
        /*
         * A task that takes longer than its own interval would otherwise be
         * started again alongside itself, and each turn would make the next
         * one slower until nothing else got a look in.
         */
        if (isset($this->running[$task->task])) {
            $this->logger->warning(
                'Scheduled task ' . $task->task . ' is still busy from its last turn; skipping this one.',
            );

            return;
        }

        $this->running[$task->task] = true;

        /*
         * In a fiber, so a task may await the REST API, and inside a catch, so
         * one that throws is logged rather than travelling up into the event
         * loop and taking the process with it.
         */
        async(function () use ($task): void {
            try {
                $task->method->invokeArgs($this->container->get($task->task), []);
            } catch (Throwable $throwable) {
                $this->logger->error(
                    'Scheduled task ' . $task->task . ' failed: ' . $throwable->getMessage(),
                    ['exception' => $throwable],
                );
            } finally {
                unset($this->running[$task->task]);
            }
        })();
    }
}
