<?php

namespace Tempcord\Interfaces;

use Evenement\EventEmitterInterface;

interface EventBus
{
    /** Return the underlying emitter to integrate with systems expecting EventEmitterInterface */
    public function getEmitter(): EventEmitterInterface;

    public function on(string $event, callable $listener): void;
    public function once(string $event, callable $listener): void;
    public function emit(string $event, array $arguments = []): void;
}