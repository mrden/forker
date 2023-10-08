<?php

namespace Mrden\Fork\Contracts;

use Mrden\Fork\Contracts\Interfaces\Cloneable;
use Mrden\Fork\Contracts\Interfaces\Forkable;
use Mrden\Fork\Contracts\Interfaces\Parental;
use Mrden\Fork\Contracts\Interfaces\Unique;
use Mrden\Fork\Exceptions\ForkException;
use Mrden\Fork\Forker;
use Mrden\Fork\Process\ExecCmdProcess;

abstract class Process implements Forkable, Cloneable, Unique
{
    /**
     * @psalm-var positive-int
     */
    protected $maxCloneCount = 5;

    /**
     * @var array
     */
    protected $params;
    /**
     * @var Parental|Process|null
     */
    private $parentProcess;
    /**
     * Number running clone
     * @psalm-var positive-int
     */
    private $runningCloneNumber = 1;
    /**
     * @var callable[]
     */
    private $afterStopHandlers = [];

    protected $needRestart = false;
    protected $excludeParamsKey = [];

    /**
     * @throws \Exception
     */
    public function __construct(array $params = [], ?Parental $parentProcess = null)
    {
        $this->params = $params;
        $this->parentProcess = $parentProcess;
        $this->checkParams();
    }

    public function run(int $cloneNumber = 1): void
    {
        if ($this->parentProcess) {
            $this->parentProcess->setIsChildContext(true);
        }
        $this->runningCloneNumber = $cloneNumber;
        \cli_set_process_title(sprintf('%s (%d)', $this->title(), $cloneNumber));

        \pcntl_signal(\SIGTERM, [$this, 'signalHandler']);
        \pcntl_signal(\SIGUSR1, [$this, 'signalHandler']);
        \pcntl_signal(\SIGUSR2, [$this, 'signalHandler']);
        \register_shutdown_function([$this, 'shutdownHandler'], $cloneNumber);

        $this->pidStorage()->save($cloneNumber, \getmypid());
        $this->prepare();
        $this->execute();

        foreach ($this->afterStopHandlers as $afterStopHandler) {
            $afterStopHandler();
        }
    }

    public function pid(int $cloneNumber = null): int
    {
        $cloneNumber = $cloneNumber ?? $this->getRunningCloneNumber();
        return $this->pidStorage()->get($cloneNumber);
    }

    public function maxCloneCount(): int
    {
        return $this->maxCloneCount;
    }

    public function uuid(): string
    {
        $params = [];
        foreach ($this->params as $key => $param) {
            if (\in_array($key, $this->excludeParamsKey)) {
                continue;
            }
            $params[$key] = $param;
        }

        return \get_class($this) . \serialize($params);
    }

    /**
     * @throws ForkException
     */
    public function shutdownHandler(int $number): void
    {
        $this->pidStorage()->remove($number);
        if ($this->needRestart) {
            $restartProcess = new ExecCmdProcess([
                'cmd' => $this->getCommand($number),
            ]);
            $forker = new Forker($restartProcess);
            $forker->run();
        }
    }

    public function signalHandler(int $signo): void
    {
        switch ($signo) {
            case \SIGTERM:
                $this->terminate();
                break;
            case \SIGUSR1:
                $this->stop();
                break;
            case \SIGUSR2:
                $this->restart();
                break;
        }
    }

    protected function getCommand(int $number): string
    {
        $forkerBinary = __DIR__ . '/../../bin/forker';
        $command = sprintf(
            '%s %s --process="%s" --count=%d --clone_number=%d',
            PHP_BINARY,
            $forkerBinary,
            static::class,
            $number,
            $number
        );
        foreach ($this->params as $name => $value) {
            $command .= ' --process-' . $name . '="' . $value .'"';
        }
        return $command;
    }

    protected function terminate(): void
    {
    }

    protected function stop(?callable $afterStop = null): void
    {
        if ($afterStop !== null) {
            $this->afterStopHandlers[] = $afterStop;
        }
    }

    protected function restart(): void
    {
        $this->needRestart = true;
        $this->stop();
    }

    protected function getParams(): array
    {
        return $this->params;
    }

    protected function getRunningCloneNumber(): int
    {
        return $this->runningCloneNumber;
    }

    private function title(): string
    {
        $title = \get_class($this) . ($this->params ? ' ' . $this->paramToString() : '');
        if ($this->parentProcess) {
            $parentPid = $this->parentProcess->pid();
            if ($parentPid) {
                $title = "$parentPid => $title";
            }
        }
        return $title;
    }

    protected function paramToString(): string
    {
        $params = [];
        foreach ($this->params as $key => $param) {
            if (\mb_strwidth($param) > 25) {
                $params[$key] = \mb_strimwidth($param, 0, 10, '...') . \mb_substr($param, -15);
            } else {
                $params[$key] = $param;
            }
        }

        return \preg_replace('|\s+|', ' ', '[' . trim(str_replace(
            ['array (', ')'],
            '',
            \var_export($params, true)
        ), " \t\n\r,") . ']');
    }

    /**
     * Checking process input parameters
     * @throws \Exception
     */
    abstract protected function checkParams(): void;

    /**
     * Prepare to execute (for example, db connection to use in new thread)
     */
    abstract protected function prepare(): void;

    /**
     * Base logic of the process
     */
    abstract protected function execute(): void;

    /**
     * Storage for process pid
     */
    abstract protected function pidStorage(): Storage;
}
