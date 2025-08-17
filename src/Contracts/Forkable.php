<?php

namespace Mrden\Forker\Contracts;

interface Forkable
{
    /**
     * @psalm-param positive-int $cloneNumber
     */
    public function run(int $cloneNumber): void;

    public function getProcessManager(): ProcessManagerInterface;

    public function getPidStorage(): PidStorage;

    public function signalHandler(int $signo): void;

    public function addAfterStopCallback(callable $afterStop): void;

    public function addAfterShutdownCallback(callable $afterShutdown): void;
}
