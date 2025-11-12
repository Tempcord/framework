<?php

namespace Tests\Fixtures\Commands;

use Tempcord\Attributes\Commands\Command;

#[Command(description: 'test')]
class InvokableCommand
{

    public function __invoke()
    {

    }

}