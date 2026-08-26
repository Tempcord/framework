<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

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
