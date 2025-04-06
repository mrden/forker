<?php

namespace Tests;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Contracts\PidStorage;
use Mrden\Forker\Storage\FilePidStorage;

class TestSingleProcess extends Process
{
    protected FilePidStorage $pidStorage;

    protected int $maxCloneCount = 6;

    protected function checkParams(): void
    {
    }

    public function execute(): void
    {
        \sleep($this->params['time'] ?? 11);
    }

    protected function prepare(): void
    {
    }

    protected function pidStorage(): PidStorage
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FilePidStorage($this, __DIR__ . '/../.mrden');
        }
        return $this->pidStorage;
    }
}
