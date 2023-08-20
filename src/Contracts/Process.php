<?php

namespace Mrden\Fork\Contracts;

abstract class Process implements Forkable, Cloneable
{
    /**
     * @psalm-var positive-int
     */
    protected $maxCloneCount = 5;

    /**
     * @var array
     */
    private $params;
    /**
     * @var Parental|null
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

    /**
     * @throws \Exception
     */
    public function __construct(array $params = [], ?Parental $parentProcess = null)
    {
        $this->params = $params;
        $this->parentProcess = $parentProcess;
        $this->checkParams();
    }

    public function run(int $cloneNumber): void
    {
        $this->runningCloneNumber = $cloneNumber;
        if ($this->parentProcess) {
            $this->parentProcess->setIsParent(true);
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
        $this->pidStorage()->remove($number);
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

    protected function stop(bool $terminate = false, ?callable $afterStop = null): void
    {
        if ($afterStop !== null) {
            $this->afterStopHandlers[] = $afterStop;
        }
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
        return \get_class($this) . ($this->params ? ' ' . $this->paramToString() : '');
    }

    protected function paramToString(): string
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
    abstract protected function pidStorage(): ProcessPidStorage;
}
