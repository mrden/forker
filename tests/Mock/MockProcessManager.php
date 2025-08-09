<?php

namespace Tests\Mock;

use Mrden\Forker\Contracts\PosixProcessManagerInterface;
use Mrden\Forker\Contracts\ProcessManagerInterface;

class MockProcessManager implements PosixProcessManagerInterface
{
    /**
     * @var array<int, bool> Карта запущенных процессов (PID => isRunning)
     */
    private array $runningProcesses = [];

    /**
     * @var int Счетчик для генерации PID
     */
    private int $pidCounter = 1000;

    /**
     * @var bool Эмуляция CLI-режима
     */
    private bool $isCliMode = true;

    /**
     * @var bool Эмуляция дочернего процесса
     */
    private bool $isChildProcess = false;

    public function fork(): int
    {
        if ($this->isChildProcess) {
            return 0; // Эмуляция дочернего процесса
        }

        $pid = ++$this->pidCounter;
        $this->runningProcesses[$pid] = true;
        return $pid;
    }

    public function sendSignal(int $pid, int $signal): bool
    {
        if (!isset($this->runningProcesses[$pid])) {
            return false;
        }

        // Определяем константы сигналов
        $SIGKILL = \defined('SIGKILL') ? SIGKILL : 9;
        $SIGTERM = \defined('SIGTERM') ? SIGTERM : 15;

        // Обрабатываем только SIGKILL и SIGTERM
        if ($signal === $SIGKILL || $signal === $SIGTERM) {
            $this->runningProcesses[$pid] = false;
        }

        return true;
    }

    public function setSignalHandler(int $signal, callable|int $handler): bool
    {
        // Просто имитируем успешную установку обработчика
        return true;
    }

    public function isProcessRunning(int $pid): bool
    {
        return $this->runningProcesses[$pid] ?? false;
    }

    public function isCli(): bool
    {
        return $this->isCliMode;
    }

    public function dispatchSignals(): void
    {
        // Эмуляция обработки сигналов - просто пустая реализация
    }

    public function getCurrentPid(): ?int
    {
        return $this->pidCounter;
    }

    public function waitForProcessStop(int $pid, int $maxWaitTime): bool
    {
        // В мок-классе эмулируем поведение ожидания завершения процесса
        if (!isset($this->runningProcesses[$pid])) {
            return true; // Процесс не найден - считаем что он уже завершен
        }

        // Если процесс еще работает, принудительно останавливаем его
        if ($this->runningProcesses[$pid]) {
            $this->runningProcesses[$pid] = false;
        }

        // В мок-версии всегда успешно завершаем процесс
        return true;
    }

    /**
     * Устанавливает эмуляцию CLI-режима
     */
    public function setCliMode(bool $isCliMode): void
    {
        $this->isCliMode = $isCliMode;
    }

    /**
     * Эмулирует дочерний процесс при следующем вызове fork()
     */
    public function emulateChildProcess(bool $isChild): void
    {
        $this->isChildProcess = $isChild;
    }

    public function requestGracefulShutdown(int $pid): bool
    {
        if (!$this->isProcessRunning($pid)) {
            return true;
        }

        return $this->sendSignal($pid, \SIGTERM);
    }

    /**
     * Принудительно останавливает процесс (для тестирования)
     */
    public function forceKillProcess(int $pid): bool
    {
        if (isset($this->runningProcesses[$pid])) {
            $this->runningProcesses[$pid] = false;
        }
        return true;
    }

    /**
     * Очищает список процессов
     */
    public function reset(): void
    {
        $this->runningProcesses = [];
        $this->pidCounter = 1000;
    }
}
