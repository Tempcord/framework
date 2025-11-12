<?php

namespace Playground\Commands;

use Ragnarok\Fenrir\Parts\User;
use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Option;
use Tempest\Console\Console;

#[Command]
readonly class PingCommand
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