<?php

namespace Tests;

use Tests\Traits\ProcessFileStorageTrait;

class TestSingleProcess extends \Mrden\Fork\Contracts\Process
{
    use ProcessFileStorageTrait;

    protected function checkParams(): void
    {
    }

    public function execute(int $cloneNumber): void
    {
        sleep($this->params['time'] ?? 11);
    }

    protected function prepare(int $cloneNumber): void
    {
    }
}