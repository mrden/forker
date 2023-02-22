<?php

namespace Mrden\Fork\Contracts;

use Mrden\Fork\Forker;

abstract class DaemonWatcherProcess extends DaemonProcess
{
    final public function maxCloneCount(): int
    {
        return 1;
    }

    public function stop(bool $terminate = false, ?callable $afterStop = null): void
    {
        parent::stop($terminate, function () use ($afterStop, $terminate) {
            if (!$terminate) {
                foreach ($this->processes() as $process) {
                    $processObject = $this->createProcess($process);
                    $forker = new Forker($processObject);
                    $forker->stop(Forker::STOP_ALL);
                }
            }
            if ($afterStop !== null) {
                $afterStop();
            }
        });
    }

    /**
     * @throws \Mrden\Fork\Exceptions\ForkException
     */
    protected function job(): void
    {
        foreach ($this->processes() as $process) {
            $processObject = $this->createProcess($process);
            $forker = new Forker($processObject);
            $count = $process['count'] ?? 1;
            $forker->run($count);
        }
    }

    protected function checkParams(): void
    {
    }

    /**
     * @psalm-param array{process:string, params?:array} $process
     */
    private function createProcess(array $process): Process
    {
        return new $process['process']($process['params'], $this);
    }

    /**
     * @psalm-return array{array{process:string, params?:array, count?: int}}
     */
    abstract protected function processes(): array;
}