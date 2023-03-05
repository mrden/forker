<?php

use Tests\src\TestDaemonProcess;
use Tests\src\TestSingleProcess;

return [
    [
        'process' => TestDaemonProcess::class,
        'params' => ['test-param' => 5],
        'count' => 2,
    ],
    [
        'process' => TestSingleProcess::class,
        'params' => [
            'time' => 11,
        ],
        'count' => 1,
    ],
    [
        'process' => TestSingleProcess::class,
        'params' => [
            'time' => 25,
        ],
        'count' => 1,
    ],
];
