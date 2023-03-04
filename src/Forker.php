<?php

namespace Mrden\Fork;

use Mrden\Fork\Contracts\Cloneable;
use Mrden\Fork\Contracts\DataPreparable;
use Mrden\Fork\Contracts\Forkable;
use Mrden\Fork\Contracts\SpecificCountCloneable;
use Mrden\Fork\Exceptions\ForkException;
use Mrden\Fork\Process\CallableProcess;

final class Forker
{
    public const STOP_ALL = -1;
    /**
     * @var Forkable|callable
     */
    private $process;

    public function __construct($process)
    {
        if (is_callable($process)) {
            $process = new CallableProcess($process);
        }
        if (!$process instanceof Forkable) {
            throw new \InvalidArgumentException(
                'Incorrect process realization (must be callable or \Mrden\Fork\Contracts\Forkable).'
            );
        }
        $this->process = $process;
        \pcntl_async_signals(true);
    }

    /**
     * @throws ForkException
     */
    public function run(int $count = 1): void
    {
        if ($this->process instanceof DataPreparable) {
            $this->process->prepareData();
        }
        \pcntl_signal(\SIGCHLD, \SIG_IGN);
        for ($number = 1; $number <= $this->cloneCount($count); $number++) {
            $this->runItem($number);
        }
    }

    public function stop(int $count, int $number = null): void
    {
        $count = $this->cloneCount($count);
        if ($number === null) {
            for ($i = 1; $i <= $count; $i++) {
                $this->stop($count, $i);
            }
        } else {
            if ($number > $count) {
                return;
            }
            $currentPid = $this->process->pid($number);
            if ($currentPid > 0) {
                \posix_kill($currentPid, \SIGUSR1);
            }
        }
    }

    private function cloneCount(int $count): int
    {
        if ($this->process instanceof Cloneable) {
            if ($count == self::STOP_ALL) {
                $count = $this->process->maxCloneCount();
            } else {
                if ($this->process instanceof SpecificCountCloneable) {
                    $count = $this->process->countOfClones();
                }
                if ($count > $this->process->maxCloneCount()) {
                    $count = $this->process->maxCloneCount();
                }
            }
        } else {
            $count = 1;
        }
        return $count;
    }

    /**
     * @throws ForkException
     */
    private function runItem(int $number): void
    {
        if ($this->isRunning($number)) {
            return;
        }
        $pid = \pcntl_fork();
        switch ($pid) {
            case -1:
                // Fork error
                throw new ForkException(sprintf(
                    'Process %s not forked',
                    \get_class($this->process)
                ));
            case 0:
                // Child process logic
                $this->process->run($number);
                exit;
            default:
                // Parent process logic
                break;
        }
    }

    private function isRunning(int $number): bool
    {
        $pid = $this->process->pid($number);
        if ($pid) {
            if (\posix_kill($pid, 0)) {
                return true;
            }
            return false;
        }
        return false;
    }
}
