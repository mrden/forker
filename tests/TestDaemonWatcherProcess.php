<?php

namespace Tests;

use Tests\Traits\ProcessFileStorageTrait;

class TestDaemonWatcherProcess extends \Mrden\Fork\Contracts\DaemonWatcherProcess
{
    use ProcessFileStorageTrait;

    protected $period = 25;

    protected function processes(): array
    {
        return [
            [
                'process' => TestDaemonWatcherProcess1::class,
                'params' => [],
                'count' => 5, // должен запуститься 1, т.к. это \Mrden\Fork\Contracts\DaemonWatcherProcess
            ],
            [
                'process' => TestDaemonProcess::class,
                'params' => ['test-param' => 5],
                'count' => 5,
            ],
            [
                'process' => TestSingleProcess::class,
                'params' => ['time' => 35],
                'count' => 2,
            ],
            [
                'process' => TestSingleProcess::class,
                'params' => [],
                'count' => 1,
            ],
        ];
    }

    protected function prepare(int $cloneNumber): void
    {
    }
}