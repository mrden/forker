<?php

namespace Mrden\Forker\Contracts;

interface Forkable
{
    /**
     * @psalm-param positive-int $cloneNumber
     */
    public function run(int $cloneNumber = 1): void;

    public function signalHandler(int $signo): void;

    public function addAfterStopCallback(callable $afterStop): void;
}
