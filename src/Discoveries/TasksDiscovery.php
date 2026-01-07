<?php

declare(strict_types=1);

namespace Tempcord\Discoveries;

use Tempcord\Attributes\Task;
use Tempcord\Registries\TasksRegistry;
use Tempest\Container\Container;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class TasksDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly TasksRegistry $registry
    ) {}

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getPublicMethods() as $method) {
            $task = $method->getAttribute(Task::class);

            if ($task === null) {
                continue;
            }

            $task->setReflector($method);
            $this->registry->register($task);
        }
    }

    public function createCachePayload(): string
    {
        return serialize($this->registry->getTasks());
    }

    public function restoreCachePayload(Container $container, string $payload): void
    {
        $tasks = unserialize($payload);

        foreach ($tasks as $task) {
            $this->registry->register($task);
        }
    }

    public function apply(): void
    {
    }
}
