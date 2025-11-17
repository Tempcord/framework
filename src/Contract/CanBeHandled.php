<?php

namespace Tempcord\Contract;

use Tempcord\Support\Commands\CommandHandler;

interface CanBeHandled
{
    public CommandHandler $handler {
        get;
    }

}