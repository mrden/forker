<?php

namespace Mrden\Fork\Contracts;

interface Forkable
{
    public function run(int $cloneNumber): void;
    public function pid(int $cloneNumber): int;
}
