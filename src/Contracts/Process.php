<?php

namespace Mrden\Forker\Contracts;

use Mrden\Forker\Exceptions\ForkException;
use Mrden\Forker\Forker;
use Mrden\Forker\Process\RestartWithForkerBinaryProcess;
use Mrden\Forker\ProcessManager\PosixProcessManager;
use Mrden\Forker\Storage\FilePidStorage;

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
    protected array $excludeParamsKey = ['pid-storage-class', 'process-manager-class'];
    /**
     * Number running clone
     * @psalm-var positive-int
     */
    private int $runningCloneNumber = 1;
    /**
     * @var callable[]
     */
    private array $afterStopCallbacks = [];
    /**
     * @var callable[]
     */
    private array $afterShutdownCallbacks = [];

    private bool $callbacksExecuted = false;
    /**
     * @var null|string
     */
    protected string|null $nameProcess = null;

    protected PosixProcessManagerInterface $processManager;
    protected PidStorage $pidStorage;

    /**
     * @param class-string<PidStorage>|null $pidStorageClassName
     */
    public function __construct(
        array $params = [],
        ?ProcessManagerInterface $processManager = null,
        ?string $pidStorageClassName = null
    ) {
        $this->params = $params;
        $pidStorageClassName = $pidStorageClassName ?? ($params['pid-storage-class'] ?? FilePidStorage::class);
        $this->pidStorage = \is_subclass_of($pidStorageClassName, PidStorage::class)
            ? new $pidStorageClassName($this)
            : new FilePidStorage($this);
        if (!$processManager) {
            $processManagerClassname = $params['process-manager-class'] ?? PosixProcessManager::class;
            $processManager = \is_subclass_of($processManagerClassname, PosixProcessManagerInterface::class)
                ? new $processManagerClassname()
                : new PosixProcessManager();
        }
        $this->processManager = $processManager;
        $this->configure($params);
    }

    public function getProcessManager(): PosixProcessManagerInterface
    {
        return $this->processManager;
    }

    public function getPidStorage(): PidStorage
    {
        return $this->pidStorage;
    }

    /**
     * @psalm-param positive-int $cloneNumber
     * @throws ForkException
     */
    public function run(int $cloneNumber): void
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

    protected function initRestartMySelf(): void
    {
        $this->initGracefulShutdown();
        $this->addAfterShutdownCallback(function (): void {
            $forker = new Forker(new RestartWithForkerBinaryProcess($this));
            $forker->run();
        });
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

    private function executeAfterShutdownCallbacks(): void
    {
        foreach ($this->afterShutdownCallbacks as $callback) {
            try {
                $callback();
            } catch (\Throwable $e) {
                \error_log("Ошибка выполнения after-shutdown колбэка: " . $e);
            }
        }
    }

    private function executeWithSignalHandling(): void
    {
        try {
            $this->execute();
        } finally {
            $this->getProcessManager()->dispatchSignals();
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
            $this->executeAfterStopCallbacks();
        }
        $this->executeAfterShutdownCallbacks();
    }

    public function signalHandler(int $signo): void
    {
        switch ($signo) {
            case \SIGTERM:
            case \SIGINT:
                // Немедленная остановка
                $this->executeAfterStopCallbacks();
                exit(0);
            case \SIGUSR1:
                // Инициализация остановки работы процесса
                $this->initGracefulShutdown();
                break;
            case \SIGUSR2:
                // Инициализация перезапуска процесса
                $this->initRestartMySelf();
                break;
        }
    }

    public function addAfterStopCallback(callable $afterStop): void
    {
        $this->afterStopCallbacks[] = $afterStop;
    }

    public function addAfterShutdownCallback(callable $afterShutdown): void
    {
        $this->afterShutdownCallbacks[] = $afterShutdown;
    }

    /**
     * @psalm-return positive-int
     */
    final public function getRunningCloneNumber(): int
    {
        return $this->runningCloneNumber;
    }

    final protected function getDefaultTitle(): string
    {
        $className = $this->nameProcess ?: static::class;
        $paramsString = $this->paramsToString();
        $title = $className . $paramsString;

        return \trim($title);
    }

    final protected function getParamsWithoutExclude(): array
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

    final protected function paramsToString(): string
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
