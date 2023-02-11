<?php

namespace Tests;

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

    public function execute(): void
    {
        sleep(11);
    }

    public function stop(?callable $afterStop = null): void
    {
    }

    public function prepare(): void
    {
    }
}