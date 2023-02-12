<?php

namespace Tests;

use Mrden\Fork\Process\DaemonProcess;
use Mrden\Fork\ProcessPidStorageInterface;
use Mrden\Fork\Storage\FileStorage;
use Mrden\Fork\Traits\ProcessFileStorageTrait;

class TestDaemonProcess extends DaemonProcess
{
    use ProcessFileStorageTrait;

    protected $maxCloneProcessCount = 15;
    /**
     * @var FileStorage
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

    public function pidStorage(): ProcessPidStorageInterface
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FileStorage($this, __DIR__ . '/storage');
        }
        return $this->pidStorage;
    }
}