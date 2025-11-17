<?php

namespace Playground\Commands\User;

use Tempcord\Attributes\Commands\Subcommand;
use Tempcord\Attributes\Commands\SubcommandGroup;

#[SubcommandGroup(name: 'report', description: 'Reports management')]
class Reports
{

    #[Subcommand(description: 'Create new report for user')]
//    #[Translated(
//        key: 'reports'
//    )]
    public function create()
    {

    }

}