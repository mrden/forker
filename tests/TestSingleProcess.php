<?php

namespace Tests;

use Mrden\Fork\ProcessPidStorage;
use Mrden\Fork\Storage\FilePidStorage;
use Mrden\Fork\Traits\ProcessFileStorageTrait;

class TestSingleProcess extends \Mrden\Fork\Process
{
    use ProcessFileStorageTrait;
    /**
     * @inheritDoc
     */
    protected function checkParams(): void
    {
    }

    public function execute(int $cloneNumber): void
    {
        sleep(11);
    }

    public function stop(bool $terminate = false, ?callable $afterStop = null): void
    {
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