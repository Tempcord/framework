<?php

declare(strict_types=1);

namespace Tempcord\ConsoleCommands;

use Tempcord\Plugins\Registry;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;

final readonly class PluginsListCommand
{
    public function __construct(
        private Registry $registry,
        private Console  $console,
    ) {}

    #[ConsoleCommand(name: 'plugins:list', description: 'List all registered Tempcord plugins')]
    public function __invoke(): void
    {
        $plugins = $this->registry->all();

        if (empty($plugins)) {
            $this->console->writeln('<comment>No plugins registered.</comment>');
            $this->console->writeln('');
            $this->console->writeln('Plugins are auto-discovered from Composer packages.');
            $this->console->writeln('Create a class with #[TempcordPlugin] attribute that implements Plugin.');
            return;
        }

        $this->console->writeln('<h1>Registered Plugins</h1>');
        $this->console->writeln('');

        foreach ($plugins as $name => $plugin) {
            $this->console->writeln(sprintf(
                '  <em>%s</em> <comment>v%s</comment>',
                $name,
                $plugin->version
            ));
            $this->console->writeln(sprintf('    %s', $plugin->description));

            $middleware = $plugin->middleware();
            if (!empty($middleware)) {
                $this->console->writeln(sprintf(
                    '    <dim>Middleware:</dim> %d global',
                    count($middleware)
                ));
            }

            $namespaces = $plugin->discoveryNamespaces();
            if (!empty($namespaces)) {
                $this->console->writeln(sprintf(
                    '    <dim>Discovery:</dim> %s',
                    implode(', ', $namespaces)
                ));
            }

            $this->console->writeln('');
        }

        $this->console->writeln(sprintf(
            '<success>Total: %d plugin(s)</success>',
            count($plugins)
        ));
    }
}
