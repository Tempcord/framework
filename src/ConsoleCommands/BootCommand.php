<?php

namespace Tempcord\ConsoleCommands;

use Tempcord\Tempcord;
use Tempcord\TempcordConfig;
use Tempest\Console\Console;
use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;
use Tempest\Log\Logger;

final readonly class BootCommand
{
    public function __construct(
        private Tempcord       $tempcord,
        private Console        $console,
        private Logger         $logger,
        private TempcordConfig $config

    )
    {
    }

    #[ConsoleCommand(name: 'boot', description: 'Boots the bot')]
    public function __invoke(
        #[ConsoleArgument(description: 'Whether to register commands', aliases: ['r'])]
        bool $registerCommands = false
    ): void
    {
        $this->console->header('Booting up...');
        $this->tempcord->boot($this->logger, $this->config);
    }
}
