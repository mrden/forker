<?php

namespace Tests\src;

use Mrden\Fork\Contracts\DaemonWatcherProcess;
use Tests\src\Traits\ProcessFileStorageTrait;

class TestDaemonWatcherProcess1 extends DaemonWatcherProcess
{
    use ProcessFileStorageTrait;

    protected $period = 15;

    public function children(): array
    {
        return [
            [
                'process' => TestSingleProcess::class,
                'params' => ['limit' => 900000000],
                'count' => 1,
            ],
        ];
    }

    protected function prepare(): void
    {
    }
}
