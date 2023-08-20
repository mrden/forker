<?php

namespace Tests\src;

use Mrden\Fork\Contracts\DaemonWatcherProcess;
use Tests\src\Traits\ProcessFileStorageTrait;

class TestDaemonWatcherProcess extends DaemonWatcherProcess
{
    use ProcessFileStorageTrait;

    protected $period = 25;

    public function children(): array
    {
        return include __DIR__ . '/config.php';
    }

    protected function prepare(): void
    {
    }
}
