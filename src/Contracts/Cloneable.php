<?php

namespace Mrden\Fork\Contracts;

interface Cloneable
{
    /**
     * @psalm-return positive-int
     */
    public function maxCloneCount(): int;
}
