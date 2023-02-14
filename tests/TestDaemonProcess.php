<?php

namespace Tests;

use Mrden\Fork\ProcessPidStorage;
use Mrden\Fork\Process\DaemonProcess;
use Mrden\Fork\Storage\FilePidStorage;
use Mrden\Fork\Traits\ProcessFileStorageTrait;

class TestDaemonProcess extends DaemonProcess
{
    use ProcessFileStorageTrait;

    protected $maxCloneProcessCount = 15;
    /**
     * @var FilePidStorage
     */
    private $pidStorages = [];

    protected function job(): void
    {
        sleep(5);
    }

    protected function checkParams(): void
    {
        if (!isset($this->params['test-param'])) {
            $this->paramException('Param "test-param" required');
        }
    }

    protected function prepare(int $cloneNumber): void
    {
    }

    public function pidStorage(): ProcessPidStorage
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FilePidStorage($this, __DIR__ . '/storage');
        }
        return $this->pidStorage;
    }
}