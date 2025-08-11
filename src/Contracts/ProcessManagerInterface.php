<?php

namespace Mrden\Forker\Contracts;

interface ProcessManagerInterface
{
    public function fork(): int;
    public function isProcessRunning(int $pid): bool;
    public function isCli(): bool;
    public function getCurrentPid(): ?int;
    public function waitForProcessStop(int $pid, int $maxWaitTime): bool;
    public function requestGracefulShutdown(int $pid): bool;
    public function terminateShutdownProcess(int $pid): bool;
    public function forceKillProcess(int $pid): bool;
    public function resetSignalHandlers(): void;
    public function resetSignalHandler(int $signal): bool;

}
