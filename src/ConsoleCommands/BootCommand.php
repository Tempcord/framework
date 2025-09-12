<?php

namespace Tempcord\ConsoleCommands;

use Tempcord\Tempcord;
use Tempest\Console\Console;
use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;

final readonly class BootCommand
{
    public function __construct(
        private Tempcord $tempcord,
        private Console  $console
    ) {}

    #[ConsoleCommand(name: 'boot', description: 'Boots the bot')]
    public function __invoke(
        #[ConsoleArgument(
            description: 'Register bot commands',
            aliases: ['r']
        )]
        bool $register = false
    ): void {
        if ($register) {
            $this->console->header('Registering commands...');
            $this->tempcord->registerCommands();
        }

        $this->console->header('Booting up...');
        $this->tempcord->boot();
    }
}
