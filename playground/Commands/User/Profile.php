<?php

namespace Playground\Commands\User;

use Playground\Enums\Commands;
use Ragnarok\Fenrir\Parts\User;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\SubcommandGroup;

#[Command(name: Commands::USER)]
#[SubcommandGroup(name: 'profile', description: '')]
class Profile
{
    #[Subcommand(
        description: 'View profile',
    )]
    public function view(
        User $user
    ): void
    {

        //

    }

    #[Subcommand(
        description: 'View profile',
    )]
    public function update(): void
    {

        //

    }

}