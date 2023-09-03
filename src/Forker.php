<?php

namespace Mrden\Fork;

use Mrden\Fork\Contracts\Cloneable;
use Mrden\Fork\Contracts\Forkable;
use Mrden\Fork\Contracts\SpecificCountCloneable;
use Mrden\Fork\Exceptions\ForkException;
use Mrden\Fork\Helpers\SysInfo;

final class Forker
{
    public const STOP_ALL = -1;
    /**
     * @var Forkable
     */
    private $process;

    public function __construct(Forkable $process)
    {
        if (!SysInfo::isCli()) {
            throw new \LogicException('Forker is only used in cli mode.');
        }
        $this->process = $process;
        \pcntl_async_signals(true);
    }

    /**
     * @psalm-return list<positive-int>
     * @throws ForkException
     */
    public function run(int $count = 1, int $number = null): array
    {
        $processedPids = [];
        $count = $this->cloneCount($count);
        \pcntl_signal(\SIGCHLD, \SIG_IGN);
        if (!$number) {
            for ($number = 1; $number <= $count; $number++) {
                $processedPids = \array_values(\array_unique(\array_merge(
                    $processedPids,
                    $this->run($count, $number)
                )));
            }
        } else {
            if ($number > $count) {
                return $processedPids;
            }
            $pid = $this->runItem($number);
            if ($pid) {
                $processedPids[] = $pid;
            }
        }

        return $processedPids;
    }

    /**
     * @psalm-return list<positive-int>
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
                $pid = $this->runItem($number);
                if ($pid) {
                    $processedPids[] = $pid;
                }
            }
        }
        return $processedPids;
    }

    /**
     * @psalm-return list<positive-int>
     * @throws ForkException
     */
    public function restart(int $count, int $number = null): array
    {
        return $this->stop($count, $number, true);
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
     * @psalm-param positive-int $number
     * @psalm-return positive-int|null
     * @throws ForkException
     */
    private function runItem(int $number): ?int
    {
        if ($this->isRunning($number)) {
            return null;
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
    private function isRunning(int $number): bool
    {
        $pid = $this->process->pid($number);
        if ($pid) {
            return \posix_kill($pid, 0);
        }
        return false;
    }
}
