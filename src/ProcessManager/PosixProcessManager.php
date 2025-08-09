<?php

namespace Mrden\Forker\ProcessManager;

use Mrden\Forker\Contracts\PosixProcessManagerInterface;

class PosixProcessManager implements PosixProcessManagerInterface
{
    public function fork(): int
    {
        return \pcntl_fork();
    }

    public function getCurrentPid(): ?int
    {
        $pid = \getmypid();
        return $pid !== false ? $pid : null;
    }

    public function requestGracefulShutdown(int $pid): bool
    {
        if (!$this->isProcessRunning($pid)) {
            return true;
        }

        return $this->sendSignal($pid, \SIGTERM);
    }

    public function forceKillProcess(int $pid): bool
    {
        if (!$this->isProcessRunning($pid)) {
            return true;
        }

        return $this->sendSignal($pid, \SIGKILL);
    }

    public function isProcessRunning(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        // Сначала проверяем через /proc файловую систему, если доступна
        if (\file_exists("/proc/{$pid}/stat")) {
            $statContent = \file_get_contents("/proc/{$pid}/stat");
            if ($statContent !== false) {
                $statParts = \explode(' ', $statContent);
                // Третий элемент - это состояние процесса
                $state = $statParts[2] ?? '';
                return $state !== 'Z'; // Процесс не является зомби
            }
        }

        return \posix_kill($pid, 0);
    }

    public function waitForProcessStop(int $pid, int $maxWaitTime): bool
    {
        $startTime = \time();
        while (\time() - $startTime < $maxWaitTime) {
            if (!$this->isProcessRunning($pid)) {
                return true;
            }

            \usleep(100000);
            $this->dispatchSignals();
        }

        return !$this->isProcessRunning($pid);
    }

    public function sendSignal(int $pid, int $signal): bool
    {
        return \posix_kill($pid, $signal);
    }

    public function setSignalHandler(int $signal, callable|int $handler): bool
    {
        return \pcntl_signal($signal, $handler);
    }

    public function dispatchSignals(): void
    {
        \pcntl_signal_dispatch();
    }

    public function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }
}
