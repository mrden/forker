<?php

use Tests\src\TestDaemonProcess;
use Tests\src\TestDaemonWatcherProcess1;
use Tests\src\TestSingleProcess;

return [
    [
        'process' => TestDaemonWatcherProcess1::class,
        'params' => [],
        'count' => 5, // должен запуститься 1, т.к. это \Mrden\Fork\Contracts\DaemonWatcherProcess
    ],
    [
        'process' => TestDaemonProcess::class,
        'params' => ['test-param' => 5],
        'count' => 2,
    ],
    [
        'process' => TestSingleProcess::class,
        'count' => 2,
    ],
];
