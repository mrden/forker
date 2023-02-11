<?php

namespace Tests;

use Mrden\Fork\Process\DaemonProcess;
use Mrden\Fork\Storage\FileStorage;
use Mrden\Fork\Traits\ProcessFileStorageTrait;

class TestDaemonProcess extends DaemonProcess
{
    use ProcessFileStorageTrait;

    protected $maxChildProcessCount = 15;
    /**
     * @var FileStorage
     */
    private $pidStorages = [];

    protected function job(): void
    {
        sleep(5);
    }

    public function prepare(): void
    {
    }

    protected function checkParams(): void
    {
        if (!isset($this->params['test-param'])) {
            $this->paramException('Param "test-param" required');
        }
    }
}