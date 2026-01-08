<?php

namespace Tempcord\Registries;

use Tempcord\Support\Prompts\PromptsBucket;
use Tempest\Container\Singleton;

#[Singleton]
class PromptsRegistry
{
    public function __construct(
        protected(set) PromptsBucket $bucket,
    )
    {
    }
}
