<?php

namespace Tempcord\ConsoleCommands;

use Tempcord\Tempcord;
use Tempcord\TempcordConfig;
use Tempest\Console\Console;
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

    #[ConsoleCommand(
        name: 'boot',
        description: 'Boots the Discord bot (blocking)'
    )]
    public function __invoke(): void
    {
        $this->console->header('Booting up...');
        $this->tempcord->boot($this->logger, $this->config);
    }
}
