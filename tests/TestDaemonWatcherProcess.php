<?php

namespace Tests;

use Mrden\Fork\ProcessPidStorageInterface;
use Mrden\Fork\Storage\FileStorage;

class TestDaemonWatcherProcess extends \Mrden\Fork\Process\DaemonWatcherProcess
{
    protected $period = 30;

    public function pidStorage(): ProcessPidStorageInterface
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FileStorage($this, __DIR__ . '/storage');
        }
        return $this->pidStorage;
    }
}