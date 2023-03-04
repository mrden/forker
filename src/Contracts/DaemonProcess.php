<?php

namespace Mrden\Fork\Contracts;

abstract class DaemonProcess extends \Mrden\Fork\Contracts\Process
{
    protected $period = 0.2;
    protected $executing = true;

    public function stop(bool $terminate = false, ?callable $afterStop = null): void
    {
        $this->executing = false;
        parent::stop($terminate, $afterStop);
    }

    public function execute(): void
    {
        while ($this->executing) {
            // Восстановление pid в хранилище каждые 2 сек
            $pid = $this->pid($this->runningCloneNumber);
            if (!$pid) {
                $this->pidStorage()->save(\getmypid(), $this->runningCloneNumber);
            }
            $this->job();
            \usleep($this->period * 1000000);
        }
    }

    abstract protected function job(): void;
}
