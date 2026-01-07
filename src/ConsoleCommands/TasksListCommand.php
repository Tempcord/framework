<?php

declare(strict_types=1);

namespace Tempcord\ConsoleCommands;

use Tempcord\Tasks\Registry;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;

final readonly class TasksListCommand
{
    public function __construct(
        private Registry $tasksRegistry,
        private Console  $console
    ) {}

    #[ConsoleCommand(name: 'tasks:list', description: 'List all registered scheduled tasks')]
    public function __invoke(): void
    {
        $tasks = $this->tasksRegistry;

        if (empty($tasks)) {
            $this->console->writeln('<comment>No tasks registered</comment>');
            return;
        }

        $this->console->writeln('<info>Registered Tasks:</info>');
        $this->console->writeln('');

        foreach ($tasks as $index => $task) {
            $status = $task->enabled ? '<info>✓</info>' : '<error>✗</error>';
            $name = $task->getName();
            $schedule = $task->getScheduleDescription();
            $runOnBoot = $task->runOnBoot ? ' <comment>(runs on boot)</comment>' : '';
            $class = $task->reflector?->getDeclaringClass()->getShortName() ?? 'Unknown';

            $this->console->writeln(sprintf(
                '  %s <fg=cyan>%s</> - %s%s',
                $status,
                $name,
                $schedule,
                $runOnBoot
            ));

            $this->console->writeln(sprintf(
                '     <fg=gray>%s::%s()</>',
                $class,
                $task->reflector?->getName() ?? 'unknown'
            ));

            if ($index < count($tasks) - 1) {
                $this->console->writeln('');
            }
        }

        $this->console->writeln('');
        $this->console->writeln(sprintf(
            '<info>Total: %d task(s)</info>',
            count($tasks)
        ));
    }
}
