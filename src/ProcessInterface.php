<?php

namespace Mrden\Fork;

interface ProcessInterface
{
    public function execute(): void;
    public function stop(): void;
    public function prepare(): void;
    public function pidStorage(): ProcessPidStorageInterface;
    public function getParams(): array;
    public function getMaxChildProcess(): int;
    public function isParent(?bool $isParent = null): bool;
    public function getParentProcess(): ?ProcessInterface;
}
