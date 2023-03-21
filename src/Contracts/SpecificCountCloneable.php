<?php

namespace Mrden\Fork\Contracts;

interface SpecificCountCloneable extends Cloneable
{
    /**
     * @psalm-return positive-int
     */
    public function countOfClones(): int;
}
