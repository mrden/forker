<?php

namespace Mrden\Fork\Contracts;

interface Cloneable
{
    public function maxCloneCount(): int;
}
