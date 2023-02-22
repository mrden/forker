<?php

namespace Tests;

use Mrden\Fork\Contracts\DaemonProcess;
use Mrden\Fork\Contracts\ProcessPidStorage;
use Mrden\Fork\Storage\FilePidStorage;
use Tests\Traits\ProcessFileStorageTrait;

class TestDaemonProcess extends DaemonProcess
{
    use ProcessFileStorageTrait;

    protected $maxCloneCount = 15;
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