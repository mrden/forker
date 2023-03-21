<?php

namespace Mrden\Fork\Contracts;

interface Forkable
{
    /**
     * @psalm-param positive-int $cloneNumber
     */
    public function run(int $cloneNumber): void;

    /**
     * @psalm-param positive-int $cloneNumber
     * @psalm-return positive-int
     */
    public function pid(int $cloneNumber): int;
}
