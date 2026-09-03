<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;

/**
 * Names something that is not middleware at all, which discovery has to refuse.
 */
#[Command(description: 'Guarded by nothing of the sort', middleware: [TrackRepository::class])]
final class NotMiddlewareCommand
{
    public function __invoke(): void {}
}
