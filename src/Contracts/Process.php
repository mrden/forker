<?php

namespace Mrden\Forker\Contracts;

use Mrden\Forker\Exceptions\ForkException;
use Mrden\Forker\ProcessManager\PosixProcessManager;

abstract class Process implements Forkable, Cloneable, Unique, PidAware
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
    private array $afterStopHandlers = [];

    protected array $excludeParamsKey = [];
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
        $this->checkParams();
    }

    public function notifyPid(int $cloneNumber, int $pid): void
    {
        $this->getPidStorage()->save($cloneNumber, $pid);
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
        \cli_set_process_title(\sprintf('%s (%d)', $title, $cloneNumber));

        $this->processManager->setSignalHandler(\SIGTERM, [$this, 'signalHandler']);
        $this->processManager->setSignalHandler(\SIGUSR1, [$this, 'signalHandler']);

        \register_shutdown_function([$this, 'shutdownHandler'], $cloneNumber);

        $pid = $this->processManager->getCurrentPid();
        if ($pid === false) {
            throw new ForkException('Error get process pid');
        }

        $this->getPidStorage()->save($cloneNumber, $pid);
        $this->prepare();
        $this->executeWithSignalHandling();

        foreach ($this->afterStopHandlers as $afterStopHandler) {
            $afterStopHandler();
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
     * @psalm-param positive-int $cloneNumber
     */
    public function pid(int $cloneNumber = 0): ?int
    {
        $cloneNumber = $cloneNumber ?: $this->getRunningCloneNumber();
        return $this->getPidStorage()->get($cloneNumber);
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
     * ✅ УПРОЩЕНО: Убрана логика перезапуска
     * @psalm-param positive-int $number
     * @throws \Exception
     */
    public function shutdownHandler(int $number): void
    {
        $this->getPidStorage()->remove($number);
    }

    /**
     * ✅ УПРОЩЕНО: Убрана обработка SIGUSR2
     */
    public function signalHandler(int $signo): void
    {
        switch ($signo) {
            case \SIGTERM:
                $this->terminate();
                break;
            case \SIGUSR1:
                $this->stop();
                break;
                // ❌ УДАЛЕН: case SIGUSR2
        }
    }

    protected function terminate(): void
    {
        $this->stop();
    }

    protected function stop(?callable $afterStop = null): void
    {
        if ($afterStop !== null) {
            $this->afterStopHandlers[] = $afterStop;
        }
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
        $className = $this->nameProcess ?? \get_class($this);
        $paramsString = $this->paramsToString();
        $title = $className . $paramsString;

        // Проверяем, что заголовок не пустой после обрезки пробелов
        $trimmedTitle = trim($title);
        if (empty($trimmedTitle)) {
            return 'ForkerProcess';
        }

        return $title;
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
    abstract protected function getPidStorage(): PidStorage;
}
