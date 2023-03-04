<?php

namespace Tests\src;

use Tests\src\Traits\ProcessFileStorageTrait;

class TestDaemonWatcherProcess extends \Mrden\Fork\Contracts\DaemonWatcherProcess
{
    use ProcessFileStorageTrait;

    protected $period = 25;

    protected function processes(): array
    {
        return include __DIR__ . '/config.php';
    }

    protected function prepare(): void
    {
    }
}
