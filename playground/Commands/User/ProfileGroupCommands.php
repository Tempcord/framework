<?php

namespace Playground\Commands\User;

use Ragnarok\Fenrir\Parts\User;
use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Option;
use Tempcord\Attributes\Commands\Subcommand;
use Tempcord\Attributes\Commands\SubcommandGroup;
use Tempest\Console\Console;

#[Command(name: 'users', description: 'Users management')]
#[SubcommandGroup(name: 'profile', description: 'Profile management')]
readonly class ProfileGroupCommands
{

    public function __construct(
        private Console $console
    )
    {
    }

    #[Subcommand]
    public function update(
        #[Option(description: 'user')]
        User $user
    ): void
    {
        $this->console->warning('Hit profile update');
    }

}