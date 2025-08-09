<?php

namespace Mrden\Forker\Contracts;

interface PosixProcessManagerInterface extends ProcessManagerInterface
{
    public function sendSignal(int $pid, int $signal): bool;
    public function setSignalHandler(int $signal, callable|int $handler): bool;
    public function dispatchSignals(): void;

}
