<?php

namespace Tests\src;

use Mrden\Fork\Contracts\DaemonProcess;
use Tests\src\Traits\ProcessFileStorageTrait;

class TestDaemonProcess extends DaemonProcess
{
    use ProcessFileStorageTrait;

    protected $maxCloneCount = 15;

    protected function job(): void
    {
        sleep(2);
    }

    /**
     * @throws \Exception
     */
    protected function checkParams(): void
    {
        $params = $this->getParams();
        if (!isset($params)) {
            throw new \Exception('Param "test-param" required');
        }
    }

    protected function prepare(): void
    {
    }
}
