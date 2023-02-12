<?php

namespace Mrden\Fork;

use Mrden\Fork\Exceptions\ForkException;

class Forker
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
        \pcntl_signal(SIGCHLD, SIG_IGN);
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
            $currentPid = $this->process->pidStorage()->get($number);
            if ($currentPid > 0) {
                return \posix_kill($currentPid, SIGTERM);
            }
        }
        return false;
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
                $this->registerSignalHandlers();
                \register_shutdown_function([$this, 'shutdownHandler'], $number);
                $this->process->cloneNumber($number);
                $this->process->pidStorage()->save(\getmypid(), $number);
                $this->process->prepare($number);
                $this->process->execute($number);
                // Exit child process
                exit;
            default:
                // Parent process logic
                break;
        }
    }

    private function registerSignalHandlers()
    {
        \pcntl_signal(SIGTERM, [$this, 'termProcess']);
    }

    public function termProcess(int $signo): void
    {
        $this->process->stop();
    }

    public function shutdownHandler(int $number): void
    {
        if (!$this->process->isParent()) {
            $this->process->pidStorage()->remove($number);
        }
    }

    private function isRunning(int $number): bool
    {
        return (bool)$this->process->pidStorage()->get($number);
    }
}
