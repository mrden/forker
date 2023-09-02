<?php

namespace Mrden\Fork\Contracts;

abstract class DaemonProcess extends Process
{
    protected $period = 0.2;
    protected $executing = true;

    public function stop(?callable $afterStop = null): void
    {
        $this->executing = false;
        parent::stop($afterStop);
    }

    public function execute(): void
    {
        while ($this->executing) {
            // Restore pid in storage every iteration
            $pid = $this->pid($this->getRunningCloneNumber());
            if (!$pid) {
                $this->pidStorage()->save(\getmypid(), $this->getRunningCloneNumber());
            }
            $this->job();
            \usleep($this->period * 1000000);
        }
    }

    abstract protected function job(): void;
}
