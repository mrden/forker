<?php

namespace Mrden\Fork;

use Mrden\Fork\Exceptions\ForkException;

final class Forker
{
    /**
     * @var ProcessInterface
     */
    private $process;

    public function __construct(ProcessInterface $process)
    {
        $this->process = $process;
        \pcntl_async_signals(true);
    }

    /**
     * @throws ForkException
     */
    public function run(int $count = 1): void
    {
        \pcntl_signal(\SIGCHLD, \SIG_IGN);
        if ($count > $this->process->getMaxCloneProcessCount()) {
            $count = $this->process->getMaxCloneProcessCount();
        }
        for ($number = 1; $number <= $count; $number++) {
            $this->runItem($number);
        }
    }

    public function stop(int $number = null): bool
    {
        if ($number === null) {
            for ($i = 1; $i <= $this->process->getMaxCloneProcessCount(); $i++) {
                $this->stop($i);
            }
            return true;
        } else {
            $currentPid = $this->process->getPid($number);
            if ($currentPid > 0) {
                return \posix_kill($currentPid, \SIGUSR1);
            }
        }
        return false;
    }

    /**
     * @throws ForkException
     */
    private function runItem(int $number): void
    {
        // is running
        if ($this->process->getPid($number)) {
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
}
