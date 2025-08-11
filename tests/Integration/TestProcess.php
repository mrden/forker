<?php

namespace Tests\Integration;

use Mrden\Forker\Contracts\Process;

class TestProcess extends Process
{
    protected string $logFile;
    protected int $runDuration;
    private bool $shouldExit = false;
    protected string|null $nameProcess = 'test_process';

    protected function configure(array $params): void
    {
        $this->logFile = $params['log_file'] ?? \sys_get_temp_dir() . '/forker_test_' . \uniqid() . '.log';
        $this->runDuration = $params['run_duration'] ?? 5; // секунд
    }

    protected function prepare(): void
    {
        // Создаем лог-файл
        \touch($this->logFile);
    }

    protected function execute(): void
    {
        $startTime = \time();
        $pid = \getmypid();

        // Записываем старт процесса
        \file_put_contents($this->logFile, "Process {$pid} started at " . \date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);

        // Основной цикл процесса
        while (!$this->shouldExit && (\time() - $startTime) < $this->runDuration) {
            // Записываем heartbeat каждую секунду
            \file_put_contents($this->logFile, "Process {$pid} heartbeat at " . \date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);

            // Обрабатываем сигналы
            $this->processManager->dispatchSignals();
            \sleep(1);
        }

        // Записываем завершение процесса
        \file_put_contents($this->logFile, "Process {$pid} finished at " . \date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
    }

    protected function initGracefulShutdown(): void
    {
        $this->shouldExit = true;
    }

    public function setMaxCloneCount(int $count): void
    {
        $this->maxCloneCount = $count;
    }

    public function getLogContents(): string
    {
        return \file_exists($this->logFile) ? \file_get_contents($this->logFile) : '';
    }

    public function cleanupLogFile(): void
    {
        if (\file_exists($this->logFile)) {
            //\unlink($this->logFile);
        }
    }

    protected function cleanup(): void
    {
    }
}
