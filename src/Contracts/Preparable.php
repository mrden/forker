<?php

namespace Mrden\Forker\Contracts;

interface Preparable
{
    public function prepareToFork(): void;
}
