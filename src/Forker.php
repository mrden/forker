<?php

namespace Mrden\Forker;

use Mrden\Forker\Contracts\Cloneable;
use Mrden\Forker\Contracts\Forkable;
use Mrden\Forker\Contracts\PidAware;
use Mrden\Forker\Contracts\Preparable;
use Mrden\Forker\Contracts\ProcessManagerInterface;
use Mrden\Forker\Contracts\SpecificCountCloneable;
use Mrden\Forker\Exceptions\ForkException;
use Mrden\Forker\ProcessManager\PosixProcessManager;

final class Forker
{
    private Forkable $process;

    private ProcessManagerInterface $processManager;

    /**
     * @throws ForkException
     */
    public function __construct(Forkable $process, ?ProcessManagerInterface $processManager = null)
    {
        $this->processManager = $processManager ?? new PosixProcessManager();

        if (!$this->processManager->isCli()) {
            throw new ForkException('Forker is only used in cli mode.');
        }

        $this->process = $process;
    }

    /**
     * @psalm-param positive-int|null $number
     * @return list<int>
     * @throws ForkException
     */
    public function run(int $cloneCount = 1, int $number = null): array
    {
        if ($this->process instanceof Preparable) {
            $this->process->prepareToFork();
        }
        return $this->runProcess($cloneCount, $number);
    }

    /**
     * @psalm-param positive-int|null $number
     * @return list<int>
     * @throws ForkException
     */
    private function runProcess(int $cloneCount = 1, int $number = null): array
    {
        $processedPids = [];
        $cloneCount = $this->cloneCount($cloneCount);
        $this->processManager->setSignalHandler(\SIGCHLD, \SIG_IGN);

        if ($number === null) {
            for ($number = 1; $number <= $cloneCount; $number++) {
                $this->processManager->dispatchSignals();
                $processedPids = \array_values(\array_unique(\array_merge(
                    $processedPids,
                    $this->runProcess($cloneCount, $number)
                )));
            }
        } else {
            if ($number > $cloneCount) {
                return $processedPids;
            }
            $this->processManager->dispatchSignals();
            $processedPids[] = $this->runCloneItem($number);
        }

        return $processedPids;
    }

    /**
     * @psalm-param positive-int|null $number
     * @return list<int>
     * @throws ForkException
     */
    public function stop(int $count, int $number = null): array
    {
        $processedPids = [];
        $count = $this->cloneCount($count);

        if ($number === null) {
            for ($i = 1; $i <= $count; $i++) {
                $processedPids = \array_values(\array_unique(\array_merge(
                    $processedPids,
                    $this->stop($count, $i)
                )));
            }
        } else {
            if ($number > $count) {
                return $processedPids;
            }
            $currentPid = $this->process->pid($number);
            if ($currentPid > 0) {
                $this->processManager->sendSignal($currentPid, \SIGUSR1);
                $processedPids[] = $currentPid;
            }
        }
        return $processedPids;
    }

    /**
     * @return list<int>
     * @throws ForkException
     */
    public function stopAll(): array
    {
        $maxCount = $this->process instanceof Cloneable
            ? $this->process->maxCloneCount()
            : 1;

        return $this->stop($maxCount);
    }


    /**
     * @psalm-param positive-int|null $number
     * @psalm-param positive-int $stopTimeout
     * @return list<int>
     * @throws ForkException
     */
    public function restart(int $count, int $number = null, int $stopTimeout = 10): array
    {
        $processedPids = [];
        $count = $this->cloneCount($count);

        if ($number === null) {
            for ($i = 1; $i <= $count; $i++) {
                $processedPids = array_values(array_unique(array_merge(
                    $processedPids,
                    $this->restart($count, $i, $stopTimeout)
                )));
            }
        } else {
            if ($number > $count) {
                return $processedPids;
            }

            $currentPid = $this->process->pid($number);
            if ($currentPid > 0 && $this->processManager->isProcessRunning($currentPid)) {
                // Останавливаем старый процесс с SIGUSR1
                $this->processManager->sendSignal($currentPid, \SIGUSR1);

                // Ожидаем остановки
                $this->processManager->waitForProcessStop($currentPid, $stopTimeout);
            }

            // Запускаем новый процесс
            $processedPids[] = $this->runCloneItem($number);
        }

        return $processedPids;
    }

    /**
     * @psalm-param positive-int $stopTimeout
     * @return list<int>
     * @throws ForkException
     */
    public function restartAll(int $stopTimeout = 10): array
    {
        $maxCount = $this->process instanceof Cloneable
            ? $this->process->maxCloneCount()
            : 1;

        return $this->restart($maxCount, null, $stopTimeout);
    }


    private function cloneCount(int $count): int
    {
        if (!$this->process instanceof Cloneable) {
            return 1;
        }
        if ($this->process instanceof SpecificCountCloneable) {
            $count = $this->process->countOfClones();
        }
        return \min($count, $this->process->maxCloneCount());
    }

    /**
     * @psalm-param positive-int $number
     * @throws ForkException
     */
    private function runCloneItem(int $number): int
    {
        $runningPid = $this->getRunningPid($number);
        if ($runningPid !== null) {
            return $runningPid;
        }

        $pid = $this->processManager->fork();
        switch ($pid) {
            case -1:
                // Fork error
                throw new ForkException(sprintf(
                    'Process %s not forked',
                    \get_class($this->process)
                ));
            case 0:
                // Child process logic
                $this->processManager->setSignalHandler(\SIGUSR1, static function ($signo) {
                    // Graceful shutdown
                    exit(0);
                });

                $this->process->run($number);
                exit;
            default:
                // Parent process logic
                if ($this->process instanceof PidAware) {
                    $this->process->notifyPid($number, $pid);
                }

                return $pid;
        }
    }

    /**
     * @psalm-param positive-int $number
     */
    private function getRunningPid(int $number): ?int
    {
        $runningPid = $this->process->pid($number);
        if ($runningPid !== null && $runningPid > 0) {
            return $this->processManager->isProcessRunning($runningPid) ? $runningPid : null;
        }
        return null;
    }
}
