<?php

namespace Mrden\Forker\Contracts;

interface Forkable
{
    /**
     * @psalm-param positive-int $cloneNumber
     */
    public function run(int $cloneNumber = 1): void;

    /**
     * @psalm-param positive-int $cloneNumber
     */
    public function pid(int $cloneNumber = 0): ?int;
}
