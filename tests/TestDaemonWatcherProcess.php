<?php

namespace Tests;

class TestDaemonWatcherProcess extends \Mrden\Fork\Process\DaemonWatcherProcess
{
    protected $period = 30;
}