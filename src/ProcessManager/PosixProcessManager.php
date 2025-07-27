<?php

namespace Mrden\Forker\ProcessManager;

use Mrden\Forker\Contracts\ProcessManagerInterface;

class PosixProcessManager implements ProcessManagerInterface
{
    public function fork(): int
    {
        return \pcntl_fork();
    }

    public function sendSignal(int $pid, int $signal): bool
    {
        return \posix_kill($pid, $signal);
    }

    public function setSignalHandler(int $signal, callable|int $handler): bool
    {
        return \pcntl_signal($signal, $handler);
    }

    public function isProcessRunning(int $pid): bool
    {
        return $pid > 0 && \posix_kill($pid, 0);
    }

    public function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    public function dispatchSignals(): void
    {
        \pcntl_signal_dispatch();
    }

    public function getCurrentPid(): ?int
    {
        $pid = \getmypid();
        return $pid !== false ? $pid : null;
    }

    public function waitForProcessStop(int $pid, int $maxWaitTime): bool
    {
        $startTime = time();
        $WNOHANG = \defined('WNOHANG') ? WNOHANG : 1;

        // Фаза 1: Graceful shutdown с неблокирующими проверками
        while (time() - $startTime < $maxWaitTime) {
            // Неблокирующая проверка через pcntl_waitpid напрямую
            $status = 0;
            $result = \pcntl_waitpid($pid, $status, $WNOHANG);

            if ($result === $pid) {
                // Процесс завершился успешно
                return true;
            } elseif ($result === -1) {
                // Ошибка - процесс уже не существует или не наш дочерний
                return true;
            } elseif ($result === 0) {
                // Процесс еще работает, продолжаем ждать
                usleep(100000); // 0.1 секунды
                $this->dispatchSignals();
            }
        }

        // Фаза 2: Принудительное завершение
        if ($this->isProcessRunning($pid)) {
            $this->sendSignal($pid, \SIGKILL);

            // После SIGKILL процесс должен завершиться быстро
            $killTimeout = 3;
            $killStart = time();

            while (time() - $killStart < $killTimeout) {
                $status = 0;
                $result = \pcntl_waitpid($pid, $status, $WNOHANG);

                if ($result === $pid || $result === -1) {
                    return true;
                }

                usleep(50000); // 0.05 секунды
            }

            // Критическая ошибка - процесс не завершился даже после SIGKILL
            trigger_error(
                sprintf('Process %d failed to terminate after SIGKILL', $pid),
                E_USER_WARNING
            );
            return false;
        }

        return true;
    }
}
