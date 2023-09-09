<?php

namespace Mrden\Fork\Contracts\Interfaces;

interface Cloneable
{
    /**
     * @psalm-return positive-int
     */
    public function maxCloneCount(): int;
}
