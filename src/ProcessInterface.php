<?php

namespace Mrden\Fork;

interface ProcessInterface
{
    public function run(int $cloneNumber): void;
    public function uuid(): string;
    public function getPid(int $cloneNumber): int;
    public function getMaxCloneProcessCount(): int;
}
