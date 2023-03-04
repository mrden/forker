<?php

namespace Mrden\Fork\Contracts;

abstract class Process implements Forkable, Cloneable
{
    protected $maxCloneCount = 5;
    protected $isParent = false;
    /**
     * @var array
     */
    protected $params;
    /**
     * @var Process|null
     */
    protected $parentProcess;
    /**
     * Number running clone
     * @var int
     */
    protected $runningCloneNumber = 1;
    /**
     * @var callable[]
     */
    protected $afterStopHandlers = [];

    public function __construct(array $params = [], ?Process $parentProcess = null)
    {
        $this->params = $params;
        $this->parentProcess = $parentProcess;
        $this->checkParams();
    }

    public function run(int $cloneNumber): void
    {
        $this->runningCloneNumber = $cloneNumber;
        if ($this->parentProcess) {
            $this->parentProcess->isParent(true);
        }
        cli_set_process_title(sprintf('%s (%d)', $this->title(), $cloneNumber));

        \pcntl_signal(\SIGTERM, [$this, 'signalHandler']);
        \pcntl_signal(\SIGUSR1, [$this, 'signalHandler']);
        \register_shutdown_function([$this, 'shutdownHandler'], $cloneNumber);

        $this->pidStorage()->save(\getmypid(), $cloneNumber);
        $this->prepare();
        $this->execute();

        foreach ($this->afterStopHandlers as $afterStopHandler) {
            $afterStopHandler();
        }
    }

    public function pid(int $cloneNumber): int
    {
        return $this->pidStorage()->get($cloneNumber);
    }

    public function maxCloneCount(): int
    {
        return $this->maxCloneCount;
    }

    public function uuid(): string
    {
        return \get_class($this) . \serialize($this->params);
    }

    public function shutdownHandler(int $number): void
    {
        if (!$this->isParent()) {
            $this->pidStorage()->remove($number);
        }
    }

    public function signalHandler(int $signo): void
    {
        switch ($signo) {
            case \SIGTERM:
                $this->stop(true);
                break;
            case \SIGUSR1:
                $this->stop();
                break;
        }
    }

    protected function isParent(?bool $isParent = null): bool
    {
        if ($isParent === null) {
            return $this->isParent;
        }
        $this->isParent = $isParent;
        return $isParent;
    }

    protected function stop(bool $terminate = false, ?callable $afterStop = null): void
    {
        if ($afterStop !== null) {
            $this->afterStopHandlers[] = $afterStop;
        }
    }

    private function title(): string
    {
        $prefix = '';
        if ($this->parentProcess) {
            $prefix = $this->parentProcess->title() . ': ';
        }
        return $prefix . \get_class($this) . ($this->params ? ' ' . $this->paramToString() : '');
    }

    private function paramToString(): string
    {
        foreach ($this->params as &$param) {
            if (\mb_strwidth($param) > 25) {
                $param = \mb_strimwidth($param, 0, 10, '...') . \mb_substr($param, -15);
            }
        }

        return '[' . trim(str_replace(
            ['array (', ')'],
            '',
            var_export($this->params, true)
        ), " \t\n\r,") . ']';
    }

    /**
     * Checking process input parameters
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
    abstract protected function pidStorage(): ProcessPidStorage;
}
