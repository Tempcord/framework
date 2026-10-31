<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;

#[Command(description: 'Records what it was called with')]
final class RecordingCommand
{
    /** @var list<string> */
    public static array $calls = [];

    public function __invoke(
        #[Option(description: 'anything')] string $subject,
    ): void {
        self::$calls[] = $subject;
    }
}
