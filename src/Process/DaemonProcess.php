<?php

namespace Mrden\Fork\Process;

use Mrden\Fork\Traits\ProcessExecutingTrait;
use Mrden\Fork\Traits\ProcessFileStorageTrait;

abstract class DaemonProcess extends \Mrden\Fork\AbstractProcess
{
    use ProcessFileStorageTrait;

    /**
     * sec
     */
    protected $period = 0.2;

    protected $executing = true;
    /**
     * @var callable[]
     */
    protected $afterStopHandlers = [];

    public function stop(?callable $afterStop = null): void
    {
        $this->executing = false;
        if ($afterStop !== null) {
            $this->afterStopHandlers[] = $afterStop;
        }
    }

    public function execute(): void
    {
        while ($this->executing) {
            $this->job();
            \usleep($this->period * 1000000);
        }
        foreach ($this->afterStopHandlers as $afterStopHandler) {
            $afterStopHandler();
        }

    }

    abstract protected function job(): void;
}