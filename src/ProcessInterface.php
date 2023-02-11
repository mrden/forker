<?php

namespace Mrden\Fork;

interface ProcessInterface
{
    public function execute(): void;
    public function stop(?callable $afterStop = null): void;
    public function prepare(): void;
    public function pidStorage(): ProcessPidStorageInterface;
    public function getParams(): array;
    public function getMaxChildProcess(): int;
    public function isParent(?bool $isParent = null): bool;
    public function cloneNumber(?int $number = null): int;
    public function getParentProcess(): ?ProcessInterface;
}
