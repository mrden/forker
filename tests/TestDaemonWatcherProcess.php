<?php

namespace Tests;

use Mrden\Fork\ProcessPidStorage;
use Mrden\Fork\Storage\FilePidStorage;

class TestDaemonWatcherProcess extends \Mrden\Fork\Process\DaemonWatcherProcess
{
    protected $period = 1;

    public function pidStorage(): ProcessPidStorage
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FilePidStorage($this, __DIR__ . '/storage');
        }
        return $this->pidStorage;
    }

    protected function prepare(int $cloneNumber): void
    {
    }
}