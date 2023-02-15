<?php

namespace Mrden\Fork\Process;

use Mrden\Fork\Forker;
use Mrden\Fork\ProcessInterface;

abstract class DaemonWatcherProcess extends DaemonProcess
{
    /**
     * @var array
     */
    protected $processes = [];

    final public function getMaxCloneProcessCount(): int
    {
        return 1;
    }

    /**
     * @throws \Mrden\Fork\Exceptions\ForkException
     */
    protected function job(): void
    {
        foreach ($this->processes as $process) {
            $processObject = $this->createProcess($process);
            $forker = new Forker($processObject);
            $count = $process['count'] ?? ($this->params['count'] ?? 1);
            $forker->run($count);
        }
    }

    /**
     * @param array{process:string, params:array} $process
     */
    private function createProcess(array $process): ProcessInterface
    {
        return new $process['process']($process['params'], $this);
    }

    /**
     * @throws \Mrden\Fork\Exceptions\ParamException
     */
    protected function checkParams(): void
    {
        if (!isset($this->params['dir-config'])) {
            $this->paramException('Not set dir config.');
        }
        if (!\file_exists($this->params['dir-config'])) {
            $this->paramException(sprintf(
                'Not exists dir config (%s).',
                $this->params['dir-config']
            ));
        }
        $json = \file_get_contents($this->params['dir-config'] . '/processes.json');
        $processes = \json_decode($json, true);
        if ($processes === null) {
            $this->paramException('Incorrect data from file in dir config.');
        }
        $this->processes = $processes;
    }

    public function stop(bool $terminate = false, ?callable $afterStop = null): void
    {
        parent::stop($terminate, function () use ($afterStop, $terminate) {
            if (!$terminate) {
                foreach ($this->processes as $process) {
                    $processObject = $this->createProcess($process);
                    $forker = new Forker($processObject);
                    $forker->stop($processObject->getMaxCloneProcessCount());
                }
            }
            if ($afterStop !== null) {
                $afterStop();
            }
        });
    }
}