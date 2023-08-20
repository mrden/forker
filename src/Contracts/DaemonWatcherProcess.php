<?php

namespace Mrden\Fork\Contracts;

use Mrden\Fork\Exceptions\ForkException;
use Mrden\Fork\Forker;

abstract class DaemonWatcherProcess extends DaemonProcess implements Parental
{
    private $isParent = false;

    final public function maxCloneCount(): int
    {
        return 1;
    }

    public function stop(bool $terminate = false, ?callable $afterStop = null): void
    {
        parent::stop($terminate, function () use ($afterStop, $terminate) {
            if (!$terminate) {
                foreach ($this->children() as $process) {
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
     * @throws ForkException
     */
    protected function job(): void
    {
        foreach ($this->children() as $process) {
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
     * @psalm-param array{process:class-string<Process>, params?:array} $process
     * @throws ForkException
     */
    private function createProcess(array $process): Process
    {
        if (!isset($process['process'])) {
            throw new ForkException('Incorrect process config');
        }
        if (!class_exists($process['process'])) {
            throw new ForkException('Not found process ' . $process['process']);
        }
        if (!is_subclass_of($process['process'], Process::class)) {
            throw new ForkException('Incorrect implementation child process ' . $process['process']);
        }
        return new $process['process']($process['params'] ?? [], $this);
    }

    public function setIsParent(bool $isParent): void
    {
        $this->isParent = $isParent;
    }

    public function shutdownHandler(int $number): void
    {
        if (!$this->isParent) {
            parent::shutdownHandler($number);
        }
    }
}
