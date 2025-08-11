<?php

namespace Mrden\Forker\Contracts;

use JetBrains\PhpStorm\NoReturn;
use Mrden\Forker\Exceptions\ForkException;
use Mrden\Forker\ProcessManager\PosixProcessManager;

abstract class Process implements Forkable, Cloneable, Unique
{
    /**
     * @psalm-var positive-int
     */
    protected int $maxCloneCount = 5;

    /**
     * @var array
     */
    protected array $params;
    /**
     * Number running clone
     * @psalm-var positive-int
     */
    private int $runningCloneNumber = 1;
    /**
     * @var callable[]
     */
    private array $afterStopCallbacks = [];

    protected array $excludeParamsKey = [];

    private bool $callbacksExecuted = false;
    /**
     * @var null|string
     */
    protected string|null $nameProcess = null;

    protected ProcessManagerInterface $processManager;

    /**
     * @throws \Exception
     */
    public function __construct(array $params = [], ?ProcessManagerInterface $processManager = null)
    {
        $this->params = $params;
        $this->processManager = $processManager ?? new PosixProcessManager();
        $this->configure($params);
    }

    /**
     * @psalm-param positive-int $cloneNumber
     * @throws ForkException
     */
    public function run(int $cloneNumber = 1): void
    {
        $this->runningCloneNumber = $cloneNumber;

        $title = $this->getDefaultTitle();
        if ($this instanceof Titled) {
            $title = $this->getTitle();
        }
        if ($title) {
            \cli_set_process_title(\sprintf('%s (%d)', $title, $cloneNumber));
        }

        \register_shutdown_function([$this, 'shutdownHandler']);

        $pid = $this->processManager->getCurrentPid();
        if ($pid === false) {
            throw new ForkException('Error get process pid');
        }

        try {
            $this->prepare();
            $this->executeWithSignalHandling();
        } finally {
            $this->executeAfterStopCallbacks();
            $this->cleanup();
        }
    }

    private function executeAfterStopCallbacks(): void
    {
        if ($this->callbacksExecuted) {
            return;
        }

        $this->callbacksExecuted = true;

        foreach ($this->afterStopCallbacks as $callback) {
            try {
                $callback();
            } catch (\Throwable $e) {
                \error_log("Ошибка выполнения after-stop колбэка: " . $e->getMessage());
            }
        }
    }


    private function executeWithSignalHandling(): void
    {
        try {
            $this->execute();
        } finally {
            $this->processManager->dispatchSignals();
        }
    }

    /**
     * @psalm-return positive-int
     */
    public function maxCloneCount(): int
    {
        return $this->maxCloneCount;
    }

    public function id(): string
    {
        return \md5(\get_class($this) . \serialize($this->getParamsWithoutExclude()));
    }

    /**
     * @throws \Exception
     */
    public function shutdownHandler(): void
    {
        if (!$this->callbacksExecuted) {
            \error_log("Экстренное выполнение after-stop колбэков в shutdown handler");
            $this->executeAfterStopCallbacks();
        }
    }

    public function signalHandler(int $signo): void
    {
        switch ($signo) {
            case \SIGTERM:
                // Немедленная остановка
                $this->terminateHandler();
                break;
            case \SIGUSR1:
                // Инициализация остановки работы процесса
                $this->initGracefulShutdown();
                break;
        }
    }

    #[NoReturn]
    public function terminateHandler(): void
    {
        $this->executeAfterStopCallbacks();
        exit(0);
    }

    public function addAfterStopCallback(callable $afterStop): void
    {
        $this->afterStopCallbacks[] = $afterStop;
    }

    /**
     * @psalm-return positive-int
     */
    protected function getRunningCloneNumber(): int
    {
        return $this->runningCloneNumber;
    }

    private function getDefaultTitle(): string
    {
        $className = $this->nameProcess ?: static::class;
        $paramsString = $this->paramsToString();
        $title = $className . $paramsString;

        return \trim($title);
    }

    protected function getParamsWithoutExclude(): array
    {
        $params = [];
        foreach ($this->params as $key => $param) {
            if (\in_array($key, $this->excludeParamsKey)) {
                continue;
            }
            $params[$key] = $param;
        }
        return $params;
    }

    protected function paramsToString(): string
    {
        $params = [];
        foreach ($this->getParamsWithoutExclude() as $key => $param) {
            if (\mb_strwidth($param) > 25) {
                $params[$key] = \mb_strimwidth($param, 0, 10, '...') . \mb_substr($param, -15);
            } else {
                $params[$key] = $param;
            }
        }

        return \preg_replace('|\s+|', ' ', '[' . \trim(\str_replace(
            ['array (', ')'],
            '',
            \var_export($params, true)
        ), " \t\n\r,") . ']');
    }

    abstract protected function configure(array $params): void;

    abstract protected function prepare(): void;

    abstract protected function cleanup(): void;

    abstract protected function execute(): void;

    abstract protected function initGracefulShutdown(): void;
}
