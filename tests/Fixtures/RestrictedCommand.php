<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Enums\Permission;
use Tempcord\Declare\Command\Command;

#[Command(
    description: 'Only for moderators',
    permissions: [Permission::KICK_MEMBERS, Permission::BAN_MEMBERS],
)]
final class RestrictedCommand
{
    public function __invoke(): void {}
}
