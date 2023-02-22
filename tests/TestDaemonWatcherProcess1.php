<?php

namespace Tests;

use Tests\Traits\ProcessFileStorageTrait;

class TestDaemonWatcherProcess1 extends \Mrden\Fork\Contracts\DaemonWatcherProcess
{
    use ProcessFileStorageTrait;

    protected $period = 15;

    protected function processes(): array
    {
        return [
            [
                'process' => TestSingleProcess::class,
                'params' => ['time' => 5],
                'count' => 2,
            ],
        ];
    }

    protected function prepare(int $cloneNumber): void
    {
    }
}