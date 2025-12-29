<?php

namespace Tempcord\Support\Traits;

use Tempcord\Tempcord;
use Tempcord\TempcordConfig;
use Tempest\Container\Inject;

trait HasTempcord
{
    #[Inject]
    private readonly Tempcord $tempcord;
    #[Inject]
    private readonly TempcordConfig $config;

}