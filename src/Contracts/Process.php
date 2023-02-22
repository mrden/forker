<?php

namespace Mrden\Fork\Contracts;

use Mrden\Fork\Exceptions\ParamException;

abstract class Process implements Forkable, Cloneable
{
    protected $maxCloneCount = 5;
    protected $isParent = false;

    protected $params;
    protected $parentProcess;
    protected $runningCloneNumber = 1;
    /**
     * @var callable[]
     */
    protected $afterStopHandlers = [];

    /**
     * @throws ParamException
     */
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
        $this->prepare($cloneNumber);
        $this->execute($cloneNumber);

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

    /**
     * @throws ParamException
     */
    protected function paramException(string $message): void
    {
        if ($this->parentProcess) {
            throw new ParamException(static::class . ': ' . $message);
        }
        throw new ParamException($message);
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
     * @throws ParamException
     */
    abstract protected function checkParams(): void;
    abstract protected function prepare(int $cloneNumber): void;
    abstract protected function execute(int $cloneNumber): void;

    abstract protected function pidStorage(): ProcessPidStorage;
}