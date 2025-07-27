<?php

namespace Mrden\Forker\Contracts;

interface ProcessManagerInterface
{
    public function fork(): int;
    public function sendSignal(int $pid, int $signal): bool;
    public function setSignalHandler(int $signal, callable|int $handler): bool;
    public function isProcessRunning(int $pid): bool;
    public function isCli(): bool;
    public function dispatchSignals(): void;
    public function getCurrentPid(): ?int;
    public function waitForProcessStop(int $pid, int $maxWaitTime): bool;
}
