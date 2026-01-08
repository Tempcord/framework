<?php

namespace Tempcord\Support;

use Tempest\Container\Singleton;

#[Singleton]
final class InteractiveModeService
{
    private bool $enabled = false;

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
