<?php

namespace Tests\Fixtures\Commands;

use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\HandledBy;

#[Command(description: 'test')]
#[HandledBy(method: 'handler_method')]
class HandledByCommand
{

    public function handler_method(): void
    {

    }

}