<?php

namespace Tests\Mock;

use Mrden\Forker\Contracts\Cloneable;
use Mrden\Forker\Contracts\Forkable;
use Mrden\Forker\Contracts\PidStorage;
use Mrden\Forker\Contracts\ProcessManagerInterface;
use Mrden\Forker\Contracts\Unique;

/**
 * Мок-класс процесса для тестирования
 */
class MockProcess implements Forkable, Cloneable, Unique
{
    /**
     * @var array<int, int>
     */
    private array $pids = [];

    private int $maxCloneCount = 1;

    /**
     * @var callable|null
     */
    private $exitCallback = null;

    private MockPidStorage $storage;
    private MockProcessManager $processManager;

    public function __construct()
    {
        $this->storage = new MockPidStorage();
        $this->processManager = new MockProcessManager();
    }

    /**
     * Возвращает максимальное количество клонов
     */
    public function maxCloneCount(): int
    {
        return $this->maxCloneCount;
    }

    /**
     * Устанавливает максимальное количество клонов
     */
    public function setMaxCloneCount(int $count): void
    {
        $this->maxCloneCount = $count;
    }

    /**
     * Метод запуска процесса
     */
    public function run(int $cloneNumber = 1): void
    {
        // Вызываем коллбэк при выходе, если он установлен
        if ($this->exitCallback !== null) {
            \call_user_func($this->exitCallback);
        }
    }

    /**
     * Устанавливает функцию обратного вызова, вызываемую при выходе
     */
    public function setExitCallback(callable $callback): void
    {
        $this->exitCallback = $callback;
    }

    /**
     * Возвращает уникальный идентификатор процесса
     */
    public function id(): string
    {
        return 'mock-process';
    }

    /**
     * Останавливает процесс
     */
    public function stop(?callable $afterStop = null): void
    {
        if ($afterStop !== null) {
            $afterStop();
        }
    }

    /**
     * Перезапускает процесс
     */
    public function restart(): void
    {
        // Нет реализации для тестов
    }

    public function signalHandler(int $signo): void
    {
        // Нет реализации для тестов
    }

    public function addAfterStopCallback(callable $afterStop): void
    {
    }

    public function addAfterShutdownCallback(callable $afterShutdown): void
    {
    }

    public function getProcessManager(): ProcessManagerInterface
    {
        return $this->processManager;
    }

    public function getPidStorage(): PidStorage
    {
        return $this->storage;
    }
}
