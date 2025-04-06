<?php

namespace Mrden\Forker;

use Mrden\Forker\Contracts\Cloneable;
use Mrden\Forker\Contracts\Forkable;
use Mrden\Forker\Contracts\Preparable;
use Mrden\Forker\Contracts\SpecificCountCloneable;
use Mrden\Forker\Exceptions\ForkException;
use Mrden\Forker\Helpers\SysInfo;

final class Forker
{
    public const STOP_ALL = -1;
    /**
     * @var Forkable
     */
    private $process;

    /**
     * @throws ForkException
     */
    public function __construct(Forkable $process)
    {
        if (!SysInfo::isCli()) {
            throw new ForkException('Forker is only used in cli mode.');
        }
        $this->process = $process;
        \pcntl_async_signals(true);
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
        \pcntl_signal(\SIGCHLD, \SIG_IGN);
        if ($number === null) {
            for ($number = 1; $number <= $cloneCount; $number++) {
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
    public function stop(int $count, int $number = null, bool $restart = false): array
    {
        $processedPids = [];
        $count = $this->cloneCount($count);
        if ($number === null) {
            for ($i = 1; $i <= $count; $i++) {
                $processedPids = \array_values(\array_unique(\array_merge(
                    $processedPids,
                    $this->stop($count, $i, $restart)
                )));
            }
        } else {
            if ($number > $count) {
                return $processedPids;
            }
            $currentPid = $this->process->pid($number);
            if ($currentPid > 0) {
                \posix_kill($currentPid, $restart ? \SIGUSR2 : \SIGUSR1);
                $processedPids[] = $currentPid;
            } elseif ($restart) {
                $processedPids[] = $this->runCloneItem($number);
            }
        }
        return $processedPids;
    }

    /**
     * @psalm-param positive-int|null $number
     * @return list<int>
     * @throws ForkException
     */
    public function restart(int $count, int $number = null): array
    {
        return $this->stop($count, $number, true);
    }

    private function cloneCount(int $count): int
    {
        if (!$this->process instanceof Cloneable) {
            return 1;
        }

        if ($count == self::STOP_ALL) {
            $count = $this->process->maxCloneCount();
        } else {
            if ($this->process instanceof SpecificCountCloneable) {
                $count = $this->process->countOfClones();
            }
            $count = \min($count, $this->process->maxCloneCount());
        }

        return $count;
    }

    /**
     * @psalm-param positive-int $number
     * @throws ForkException
     */
    private function runCloneItem(int $number): int
    {
        $runningPid = $this->runningPid($number);
        if ($runningPid !== null) {
            return $runningPid;
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
                return $pid;
        }
    }

    /**
     * @psalm-param positive-int $number
     */
    private function runningPid(int $number): ?int
    {
        $runningPid = $this->process->pid($number);
        if ($runningPid !== null) {
            return \posix_kill($runningPid, 0) ? $runningPid : null;
        }
        return null;
    }
}
