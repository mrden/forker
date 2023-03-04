<?php

namespace Tests\src;

use Tests\src\Traits\ProcessFileStorageTrait;

class TestDaemonWatcherProcess1 extends \Mrden\Fork\Contracts\DaemonWatcherProcess
{
    use ProcessFileStorageTrait;

    protected $period = 15;

    protected function processes(): array
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
