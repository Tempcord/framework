<?php

namespace Tempcord\Contract;

use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;

/**
 * Describes an item that can be built.
 * The "be build" means it can return a Fenrir builder.
 */
interface Buildable
{
    public CommandBuilder $builder {
        get;
    }

}