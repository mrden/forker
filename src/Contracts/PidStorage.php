<?php

namespace Mrden\Forker\Contracts;

abstract class PidStorage
{
    protected Unique $unique;

    public function __construct(Unique $unique)
    {
        $this->unique = $unique;
    }

    /**
     * @psalm-param positive-int $key
     */
    abstract public function get(int $key): ?int;

    /**
     * @psalm-param positive-int $key
     */
    abstract public function remove(int $key): void;

    /**
     * @psalm-param positive-int $key
     */
    abstract public function save(int $key, int $value): void;
}
