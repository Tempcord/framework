<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;

#[Command]
final class DescriptionlessCommand
{
    public function __invoke(): void {}
}
