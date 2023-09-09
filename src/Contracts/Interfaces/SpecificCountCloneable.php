<?php

namespace Mrden\Fork\Contracts\Interfaces;

interface SpecificCountCloneable extends Cloneable
{
    /**
     * @psalm-return positive-int
     */
    public function countOfClones(): int;
}
