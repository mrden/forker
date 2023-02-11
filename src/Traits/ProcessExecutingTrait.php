<?php

namespace Mrden\Fork\Traits;

trait ProcessExecutingTrait
{
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
}