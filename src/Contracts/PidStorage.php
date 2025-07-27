<?php

namespace Mrden\Forker\Contracts;

interface PidStorage
{
    /**
     * @psalm-param positive-int $index
     */
    public function get(int $index): ?int;

    /**
     * @psalm-param positive-int $index
     */
    public function remove(int $index): void;

    /**
     * @psalm-param positive-int $index
     */
    public function save(int $index, int $pid): void;
}
