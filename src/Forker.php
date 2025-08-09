<?php

namespace Mrden\Forker;

use Mrden\Forker\Contracts\Cloneable;
use Mrden\Forker\Contracts\Forkable;
use Mrden\Forker\Contracts\PidAware;
use Mrden\Forker\Contracts\Preparable;
use Mrden\Forker\Contracts\ProcessManagerInterface;
use Mrden\Forker\Contracts\SpecificCountCloneable;
use Mrden\Forker\Exceptions\ForkException;
use Mrden\Forker\Exceptions\ProcessTimeoutException;
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
        $this->setupSignalHandlers();
    }

    private function setupSignalHandlers(): void
    {
        $this->processManager->setSignalHandler(\SIGCHLD, [$this, 'childSignalHandler']);
    }

    public function childSignalHandler(int $signo): void
    {
        $this->cleanupZombieProcesses();
    }

    private function cleanupZombieProcesses(): void
    {
        while (($pid = \pcntl_waitpid(-1, $status, \WNOHANG)) > 0) {
            // Зомби-процесс с PID $pid очищен
            // Здесь можно добавить дополнительную логику если нужно
            // todo: logging if logger exists
        }
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
            $processedPids[] = $this->runCloneItem($number);
        }

        return $processedPids;
    }

    /**
     * @psalm-param positive-int|null $number
     * @return list<int>
     * @throws ForkException
     */
    public function stop(int $count, int $number = null, int $stopTimeout = 10, bool $withForcedKill = false): array
    {
        $processedPids = [];
        $count = $this->cloneCount($count);

        if ($number === null) {
            for ($i = 1; $i <= $count; $i++) {
                $processedPids = \array_values(\array_unique(\array_merge(
                    $processedPids,
                    $this->stop($count, $i, $stopTimeout, $withForcedKill)
                )));
            }
        } else {
            if ($number > $count) {
                return $processedPids;
            }
            $currentPid = $this->process->pid($number);
            if ($currentPid > 0) {
                $this->processManager->requestGracefulShutdown($currentPid);
                if ($this->processManager->waitForProcessStop($currentPid, $stopTimeout)) {
                    $processedPids[] = $currentPid;
                } elseif ($withForcedKill) {
                    $this->processManager->forceKillProcess($currentPid);

                    if ($this->processManager->waitForProcessStop($currentPid, $stopTimeout)) {
                        $processedPids[] = $currentPid;
                    } else {
                        throw new ProcessTimeoutException($currentPid, 2 * $stopTimeout);
                    }
                }
            }
        }
        return $processedPids;
    }

    /**
     * @return list<int>
     * @throws ForkException
     */
    public function stopAll(int $stopTimeout = 10, bool $withForcedKill = false): array
    {
        $maxCount = $this->process instanceof Cloneable
            ? $this->process->maxCloneCount()
            : 1;

        return $this->stop($maxCount, null, $stopTimeout, $withForcedKill);
    }


    /**
     * @psalm-param positive-int|null $number
     * @psalm-param positive-int $stopTimeout
     * @return list<int>
     * @throws ForkException
     */
    public function restart(int $count, int $number = null, int $stopTimeout = 10, bool $withForcedKill = false): array
    {
        $processedPids = [];
        $count = $this->cloneCount($count);

        if ($number === null) {
            for ($i = 1; $i <= $count; $i++) {
                $this->processManager->dispatchSignals();
                $processedPids = \array_values(\array_unique(\array_merge(
                    $processedPids,
                    $this->restart($count, $i, $stopTimeout, $withForcedKill)
                )));
            }
        } else {
            if ($number > $count) {
                return $processedPids;
            }

            $currentPid = $this->process->pid($number);
            $this->processManager->dispatchSignals();
            if ($currentPid !== null && $this->processManager->isProcessRunning($currentPid)) {
                $this->processManager->requestGracefulShutdown($currentPid);

                if ($this->processManager->waitForProcessStop($currentPid, $stopTimeout)) {
                    $processedPids[] = $this->runCloneItem($number);
                } elseif ($withForcedKill) {
                    $this->processManager->forceKillProcess($currentPid);

                    if ($this->processManager->waitForProcessStop($currentPid, $stopTimeout)) {
                        $processedPids[] = $this->runCloneItem($number);
                    } else {
                        throw new ProcessTimeoutException($currentPid, 2 * $stopTimeout);
                    }
                } else {
                    throw new ProcessTimeoutException($currentPid, $stopTimeout);
                }
            } else {
                $processedPids[] = $this->runCloneItem($number);
            }
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
        $this->processManager->dispatchSignals();
        $runningPid = $this->getRunningPid($number);
        if ($runningPid !== null) {
            return $runningPid;
        }

        $pid = $this->processManager->fork();
        switch ($pid) {
            case -1:
                // Fork error
                throw new ForkException(\sprintf(
                    'Process %s not forked',
                    \get_class($this->process)
                ));
            case 0:
                // Child process logic
                $this->processManager->setSignalHandler(\SIGTERM, static function ($signo) {
                    exit(0);
                });

                $this->process->run($number);
                exit(0);
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
