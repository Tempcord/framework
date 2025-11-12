<?php

namespace Tempcord\Support;

use Evenement\EventEmitterInterface;
use Tempcord\Interfaces\EventBus;

class FenrirEventBus implements EventBus
{
    public function __construct(private EventEmitterInterface $emitter)
    {
    }

    public function getEmitter(): EventEmitterInterface
    {
        return $this->emitter;
    }

    public function on(string $event, callable $listener): void
    {
        $this->emitter->on($event, $listener);
    }

    public function once(string $event, callable $listener): void
    {
        $this->emitter->once($event, $listener);
    }

    public function emit(string $event, array $arguments = []): void
    {
        $this->emitter->emit($event, $arguments);
    }
}