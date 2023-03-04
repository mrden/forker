<?php

namespace Mrden\Fork\Contracts;

interface SpecificCountCloneable extends Cloneable
{
    public function countOfClones(): int;
}
