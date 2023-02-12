<?php

namespace Tests;

use Mrden\Fork\ProcessPidStorageInterface;
use Mrden\Fork\Storage\FileStorage;
use Mrden\Fork\Traits\ProcessFileStorageTrait;

class TestSingleProcess extends \Mrden\Fork\AbstractProcess
{
    use ProcessFileStorageTrait;
    /**
     * @inheritDoc
     */
    protected function checkParams(): void
    {
    }

    public function execute(int $number): void
    {
        sleep(11);
    }

    public function stop(?callable $afterStop = null): void
    {
    }

    public function pidStorage(): ProcessPidStorageInterface
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FileStorage($this, __DIR__ . '/storage');
        }
        return $this->pidStorage;
    }
}