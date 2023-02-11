<?php

namespace Mrden\Fork;

use Mrden\Fork\exception\ForkException;

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
    public function run(int $count = 1, bool $reRun = false): void
    {
        \pcntl_signal(SIGCHLD, SIG_IGN);
        if ($count > $this->process->getMaxChildProcess()) {
            $count = $this->process->getMaxChildProcess();
        }
        for ($number = 1; $number <= $count; $number++) {
            $this->runItem($number, $reRun);
        }
    }

    public function stop(int $number = null): bool
    {
        if ($number === null) {
            for ($i = 1; $i <= $this->process->getMaxChildProcess(); $i++) {
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
    private function runItem(int $number, bool $reRun = false): void
    {
        if (!$reRun && $this->isRunning($number)) {
            return;
        }
        if ($reRun) {
            $this->stop($number);
            $this->runItem($number);
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
                cli_set_process_title(sprintf(
                    'Fork process %s, instance %d',
                    $this->title(),
                    $number
                ));
                if ($this->process->getParentProcess()) {
                    $this->process->getParentProcess()->isParent(true);
                }
                $this->registerSignalHandlers();
                \register_shutdown_function([$this, 'shutdownHandler'], $number);
                $this->process->pidStorage()->save(\getmypid(), $number);
                $this->process->prepare();
                $this->process->execute();
                // Exit child process
                exit;
            default:
                // Parent process logic
                break;
        }
    }

    private function title(?ProcessInterface $process = null): string
    {
        $process = $process ?? $this->process;
        $title = get_class($process);
        if ($process->getParentProcess()) {
            $title = $this->title($process->getParentProcess()) . ' > ' . $title;
        }
        return $title;
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
