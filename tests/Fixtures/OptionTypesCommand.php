<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Parts\Channel;
use Tempcord\Discord\Parts\Role;
use Tempcord\Discord\Parts\User;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;

#[Command(description: 'Covers every supported option type')]
final class OptionTypesCommand
{
    #[Subcommand(name: 'all', description: 'All supported types')]
    public function all(
        #[Option(description: 'a string')] string $text,
        #[Option(description: 'an int')] int $count,
        #[Option(description: 'a float')] float $ratio,
        #[Option(description: 'a bool')] bool $flag,
        #[Option(description: 'a user')] User $user,
        #[Option(description: 'a channel')] Channel $channel,
        #[Option(description: 'a role')] Role $role,
    ): void {}

    #[Subcommand(name: 'renamed', description: 'Option with an explicit name')]
    public function renamed(
        #[Option(description: 'renamed option', name: 'custom')] string $original,
    ): string {
        return 'renamed:' . $original;
    }
}
