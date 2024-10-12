<?php

use Tests\TestSingleProcess;

return [
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
