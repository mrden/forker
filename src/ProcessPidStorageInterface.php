<?php

namespace Mrden\Fork;

interface ProcessPidStorageInterface
{
    public function get(int $number): int;
    public function remove(int $number): void;
    public function save(int $pid, int $number): void;
}