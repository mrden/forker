<?php

namespace Tests\Integration;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Storage\FilePidStorage;
use Mrden\Forker\Contracts\PidStorage;

class ParameterTestProcess extends Process
{
    protected string $logFile;
    protected int $runDuration;
    protected string $testString;
    protected int $testInt;
    protected float $testFloat;
    protected bool $testBool;
    protected string|null $nameProcess = "parameter_test_process";

    protected function configure(array $params): void
    {
        $this->logFile = $params["log_file"] ?? \sys_get_temp_dir() . "/forker_param_test_" . \uniqid() . ".log";
        $this->runDuration = $params["run_duration"] ?? 2;
        $this->testString = $params["test_string"] ?? "default";
        $this->testInt = $params["test_int"] ?? 0;
        $this->testFloat = $params["test_float"] ?? 0.0;
        $this->testBool = $params["test_bool"] ?? false;
    }

    protected function prepare(): void
    {
        // Создаем лог-файл если он не существует
        if (!\file_exists($this->logFile)) {
            \touch($this->logFile);
        }

        // Убеждаемся, что файл доступен для записи
        if (!\is_writable($this->logFile)) {
            throw new \RuntimeException("Log file {$this->logFile} is not writable");
        }

    }

    protected function execute(): void
    {
        $pid = \getmypid();
        $content = \sprintf(
            "Process %d: string=%s, int=%d, float=%.2f, bool=%s\n",
            $pid,
            $this->testString,
            $this->testInt,
            $this->testFloat,
            $this->testBool ? "true" : "false"
        );

        \file_put_contents($this->logFile, $content, FILE_APPEND);

        \sleep($this->runDuration);
    }

    protected function cleanup(): void
    {
        if (\file_exists($this->logFile)) {
            \unlink($this->logFile);
        }
    }

    protected function initGracefulShutdown(): void
    {
    }
}
