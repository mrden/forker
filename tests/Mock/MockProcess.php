<?php

namespace Tests\Mock;

use Mrden\Forker\Contracts\Cloneable;
use Mrden\Forker\Contracts\Forkable;
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

    public function __construct()
    {
        $this->storage = new MockPidStorage();
    }

    /**
     * Уведомление от Forker о назначенном PID (в родительском процессе)
     */
    public function notifyPid(int $cloneNumber, int $pid): void
    {
        $this->pids[$cloneNumber] = $pid;
        $this->storage->save($cloneNumber, $pid);
    }

    /**
     * Получает PID процесса по его номеру
     */
    public function pid(int $cloneNumber = 0): ?int
    {
        // Сначала проверяем в хранилище
        $pidFromStorage = $this->storage->get($cloneNumber);
        if ($pidFromStorage !== null) {
            return $pidFromStorage;
        }

        // Если в хранилище нет, возвращаем из внутреннего массива
        if (isset($this->pids[$cloneNumber])) {
            // Сохраняем в хранилище для следующих запросов
            $this->storage->save($cloneNumber, $this->pids[$cloneNumber]);
            return $this->pids[$cloneNumber];
        }

        return null;
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
}
