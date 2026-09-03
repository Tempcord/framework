<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Discord\Enums\Permission;
use Tempcord\Middleware\RequiresPermissions;

/**
 * Middleware written as an object inside the attribute, which is the shape a
 * check that only needs its own arguments takes.
 */
#[Command(
    description: 'Guarded inline',
    middleware: [new RequiresPermissions([Permission::MANAGE_GUILD], 'Not for you.')],
)]
final class InlineGuardedCommand
{
    public function __invoke(): void {}
}
