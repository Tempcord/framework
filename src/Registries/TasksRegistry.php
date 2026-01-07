<?php

declare(strict_types=1);

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Extension\Extension;
use React\EventLoop\Loop;
use Tempcord\Attributes\Task;
use Tempcord\Support\Tasks\TaskRunner;
use Tempcord\Support\Tasks\TaskStats;
use Tempest\Container\Singleton;
use Tempest\Log\Logger;

use function Tempest\get;

#[Singleton]
class TasksRegistry implements Extension
{
    /** @var array<Task> */
    private array $tasks = [];

    private ?TaskRunner $runner = null;

    public function __construct() {}

    /**
     * Register a task
     */
    public function register(Task $task): void
    {
        $this->tasks[] = $task;
    }

    /**
     * Get all registered tasks
     * @return array<Task>
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Initialize the task runner when Discord is ready
     */
    public function initialize(Discord $discord): void
    {
        if (empty($this->tasks)) {
            return;
        }

        $logger = get(Logger::class);
        $loop = Loop::get();

        $this->runner = new TaskRunner($loop, $logger);

        // Schedule all tasks
        foreach ($this->tasks as $task) {
            $this->runner->schedule($task);
        }

        $logger->info("📋 Task scheduler initialized", [
            'tasks' => count($this->tasks),
        ]);
    }

    /**
     * Get the task runner (null if not initialized)
     */
    public function getRunner(): ?TaskRunner
    {
        return $this->runner;
    }

    /**
     * Cancel a specific task
     */
    public function cancelTask(string $taskName): bool
    {
        return $this->runner?->cancel($taskName) ?? false;
    }

    /**
     * Cancel all tasks
     */
    public function cancelAllTasks(): void
    {
        $this->runner?->cancelAll();
    }

    /**
     * Get statistics for all tasks
     * @return array<string, TaskStats>
     */
    public function getStats(): array
    {
        return $this->runner?->getStats() ?? [];
    }

    /**
     * Get list of scheduled task names
     * @return array<string>
     */
    public function getScheduledTasks(): array
    {
        return $this->runner?->getScheduledTasks() ?? [];
    }

    /**
     * Get number of registered tasks
     */
    public function count(): int
    {
        return count($this->tasks);
    }
}
