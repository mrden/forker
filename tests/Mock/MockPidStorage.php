<?php

namespace Tests\Mock;

use Mrden\Forker\Contracts\PidStorage;

/**
 * Мок-хранилище для тестирования
 */
class MockPidStorage implements PidStorage
{
    /**
     * @var array<int, int>
     */
    private array $data = [];

    public function save(int $index, int $pid): void
    {
        $this->data[$index] = $pid;
    }

    public function get(int $index): ?int
    {
        return $this->data[$index] ?? null;
    }

    public function remove(int $index): void
    {
        unset($this->data[$index]);
    }
}
