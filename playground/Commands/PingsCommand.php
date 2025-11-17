<?php

namespace Playground\Commands;

use Ragnarok\Fenrir\Parts\User;
use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Option;
use Tempcord\Attributes\Commands\SubcommandGroup;
use Tempest\Console\Console;

#[Command(description: "Ping command")]
#[SubcommandGroup(name: 'test', description: 'as')]
readonly class PingsCommand
{
    public function __construct(
        private Console $console
    )
    {

    }

    public function __invoke(
        #[Option(description: 'whom to ping')]
        User $user
    ): void
    {
        $this->console->info('Pinging: ' . $user->username);
    }

}