<?php

namespace Mrden\Forker\Contracts;

interface Cloneable
{
    /**
     * @psalm-return positive-int
     */
    public function maxCloneCount(): int;
}
