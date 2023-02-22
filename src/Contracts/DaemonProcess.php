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

    public function execute(int $cloneNumber): void
    {
        while ($this->executing) {
            $this->job();
            \usleep($this->period * 1000000);
        }
    }

    abstract protected function job(): void;
}