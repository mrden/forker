<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Integration\ParameterTestProcess;
use Tests\Integration\TestProcess;

#[Group("integration")]
class BinaryIntegrationTest extends TestCase
{
    private string $binPath;
    private array $processLogFiles = [];
    private array $pidStorageFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Проверяем, что мы в CLI и есть поддержка pcntl
        if (PHP_SAPI !== 'cli') {
            $this->markTestSkipped('Binary integration tests can only run in CLI mode');
        }

        if (!\extension_loaded('pcntl')) {
            $this->markTestSkipped('PCNTL extension is required for binary integration tests');
        }

        if (!\extension_loaded('posix')) {
            $this->markTestSkipped('POSIX extension is required for binary integration tests');
        }

        // Путь к бинарнику
        $this->binPath = __DIR__ . '/../bin/forker';

        if (!\file_exists($this->binPath)) {
            $this->markTestSkipped('Binary file not found: ' . $this->binPath);
        }
    }

    protected function tearDown(): void
    {
        // Останавливаем все запущенные процессы
        $this->stopAllTestProcesses();

        // Очищаем лог-файлы
        foreach ($this->processLogFiles as $logFile) {
            if (\file_exists($logFile)) {
                \unlink($logFile);
            }
        }

        // Очищаем PID storage файлы
        foreach ($this->pidStorageFiles as $pidFile) {
            if (\file_exists($pidFile)) {
                \unlink($pidFile);
            }
        }

        $this->processLogFiles = [];
        $this->pidStorageFiles = [];

        parent::tearDown();
    }

    private function executeCommand(string $command, bool $runInBackground = false): array
    {
        if ($runInBackground) {
            $outputFile = "/tmp/forker_test_output_" . \uniqid() . ".log";
            $exitCodeFile = "/tmp/forker_test_exit_" . \uniqid() . ".log";

            // Сохраняем код возврата в отдельный файл
            $fullCommand = "({$command}) > {$outputFile} 2>&1; echo \$? > {$exitCodeFile} &";
            \exec($fullCommand);

            // Даем время процессу запуститься и завершиться
            \sleep(2);

            $output = [];
            $returnCode = 0;

            if (\file_exists($outputFile)) {
                $outputContent = \file_get_contents($outputFile);
                $output = \explode("\n", \trim($outputContent));
                \unlink($outputFile);
            }

            if (\file_exists($exitCodeFile)) {
                $returnCode = (int) \trim(\file_get_contents($exitCodeFile));
                \unlink($exitCodeFile);
            }

            return [
                'output' => $output,
                'return_code' => $returnCode
            ];
        }

        $output = [];
        $returnCode = 0;
        \exec($command . ' 2>&1', $output, $returnCode);

        return [
            'output' => $output,
            'return_code' => $returnCode
        ];
    }

    public function testRunSingleProcess(): void
    {
        $logFile = $this->createTempLogFile();

        // Запускаем процесс через бинарник в фоне
        $command = \sprintf(
            'php %s --process="%s" --process-log_file=%s --process-run_duration=5 --count=1',
            $this->binPath,
            TestProcess::class,
            $logFile
        );

        $output = $this->executeCommand($command, true)['output'][0] ?? '';
        $this->assertStringContainsString('Running pid(s):', $output);

        // Извлекаем PID из вывода
        if (\preg_match('/Running pid\(s\): (\d+)/', $output, $matches)) {
            $pid = (int)$matches[1];
            $this->assertGreaterThan(0, $pid);

            // Ждем завершения процесса
            $this->waitForProcessToFinish($pid, 10);

            // Проверяем лог
            $this->assertLogFileContainsExpectedContent($logFile, $pid);
        } else {
            $this->fail('Could not extract PID from output: ' . $output);
        }
    }

    public function testRunMultipleProcesses(): void
    {
        $logFile = $this->createTempLogFile();

        $command = \sprintf(
            'php %s --process="%s" --process-log_file=%s --process-run_duration=3 --count=3',
            $this->binPath,
            TestProcess::class,
            $logFile
        );

        $output = $this->executeCommand($command, true)['output'][0] ?? '';
        $this->assertStringContainsString('Running pid(s):', $output);

        // Извлекаем PIDs из вывода
        if (\preg_match('/Running pid\(s\): (.+)/', $output, $matches)) {
            $pidsString = $matches[1];
            $pids = \array_map('intval', \explode(', ', $pidsString));

            $this->assertCount(3, $pids, 'Should run 3 processes');
            $this->assertCount(3, \array_unique($pids), 'All PIDs should be unique');

            // Ждем завершения всех процессов
            foreach ($pids as $pid) {
                $this->waitForProcessToFinish($pid, 10);
            }

            // Проверяем лог
            $logContent = \file_get_contents($logFile);
            foreach ($pids as $pid) {
                $this->assertStringContainsString("Process {$pid} started", $logContent);
            }
        } else {
            $this->fail('Could not extract PIDs from output: ' . $output);
        }
    }

    public function testStopProcess(): void
    {
        $logFile = $this->createTempLogFile();
        $outputFile = "/tmp/forker_test_output_stop_" . \getmypid() . ".log";

        // Сначала запускаем процесс в фоне
        $duration = 10;
        $runCommand = \sprintf(
            'php %s --process="%s" --process-log_file=%s --process-run_duration=%d --count=1',
            $this->binPath,
            TestProcess::class,
            $logFile,
            $duration
        );

        $runOutput = $this->executeCommand($runCommand, true)['output'][0] ?? '';

        if (!\preg_match('/Running pid\(s\): (\d+)/', $runOutput, $matches)) {
            $this->fail('Could not extract PID from run output: ' . $runOutput);
        }

        $pid = (int)$matches[1];
        $this->assertTrue($this->isProcessRunning($pid), 'Process should be running: pid = ' . $pid);

        // Теперь останавливаем процесс
        $stopCommand = \sprintf(
            'php %s --process="%s" --process-log_file=%s --stop=true --count=1',
            $this->binPath,
            TestProcess::class,
            $logFile
        );

        $this->executeCommand($stopCommand, true);

        // Проверяем, что процесс остановлен
        $this->waitForProcessToFinish($pid, $duration + 2);
        $this->assertFalse($this->isProcessRunning($pid), 'Process should be stopped');

        if (\file_exists($outputFile)) {
            \unlink($outputFile);
        }
    }

    public function testRestartProcess(): void
    {
        $logFile = $this->createTempLogFile();

        // Запускаем процесс в фоне
        $runCommand = \sprintf(
            'php %s --process="%s" --process-log_file=%s --process-run_duration=30 --count=1',
            $this->binPath,
            TestProcess::class,
            $logFile
        );

        $output = $this->executeCommand($runCommand, true)['output'][0] ?? '';
        if (!\preg_match('/Running pid\(s\): (\d+)/', $output, $matches)) {
            $this->fail('Could not extract PID from run output');
        }

        $originalPid = (int)$matches[1];

        // Перезапускаем процесс
        $restartCommand = \sprintf(
            'php %s --process="%s" --process-log_file=%s --process-run_duration=30 --restart=true --count=1',
            $this->binPath,
            TestProcess::class,
            $logFile
        );

        // Извлекаем новый PID из вывода restart команды
        $restartOutput = $this->executeCommand($restartCommand, true)['output'][0] ?? '';
        if (\preg_match('/Running pid\(s\): (\d+)/', $restartOutput, $restartMatches)) {
            $newPid = (int)$restartMatches[1];

            $this->assertNotEquals($originalPid, $newPid, 'New process should have different PID');
            $this->assertTrue($this->isProcessRunning($newPid), 'New process should be running');

            // Останавливаем новый процесс для очистки
            $stopCommand = \sprintf(
                'php %s --process="%s" --process-log_file=%s --stop=true --count=1',
                $this->binPath,
                TestProcess::class,
                $logFile
            );
            $this->executeCommand($stopCommand, true);
            $this->waitForProcessToFinish($newPid, 10);
        }

        // Проверяем, что оригинальный процесс остановлен
        $this->waitForProcessToFinish($originalPid, 10);
        $this->assertFalse($this->isProcessRunning($originalPid), 'Original process should be stopped');
    }

    public function testInvalidProcessClass(): void
    {
        $command = \sprintf(
            'php %s --process="%s" --count=1',
            $this->binPath,
            'NonExistentProcessClass'
        );

        $result = $this->executeCommand($command, true);
        $outputString = \implode("\n", $result['output']);
        $returnCode = $result['return_code'];
        $this->assertNotEquals(0, $returnCode, 'Binary should fail with invalid process class');
        $this->assertStringContainsString('Error:', $outputString);
        $this->assertStringContainsString('not found', $outputString);
    }

    public function testInvalidCount(): void
    {
        $command = \sprintf(
            'php %s --process="%s" --count=invalid',
            $this->binPath,
            TestProcess::class
        );

        $result = $this->executeCommand($command, true);
        $outputString = \implode("\n", $result['output']);
        $returnCode = $result['return_code'];
        $this->assertNotEquals(0, $returnCode, 'Binary should fail with invalid count');
        $this->assertStringContainsString('Error:', $outputString);
        $this->assertStringContainsString('Incorrect --count value', $outputString);
    }

    public function testProcessParameters(): void
    {
        $logFile = $this->createTempLogFile();

        // Тестируем передачу различных типов параметров
        $command = \sprintf(
            'php %s --process="%s" --process-log_file=%s --process-run_duration=2 --process-test_string=hello --process-test_int=42 --process-test_float=3.14 --process-test_bool=true --count=1',
            $this->binPath,
            ParameterTestProcess::class,
            $logFile
        );

        $result = $this->executeCommand($command, true);
        $outputString = \implode("\n", $result['output']);
        $returnCode = $result['return_code'];
        $this->assertEquals(0, $returnCode, 'Binary should execute successfully with parameters');
        $this->assertStringContainsString('Running pid(s):', $outputString);

        // Извлекаем PID и ждем завершения
        if (\preg_match('/Running pid\(s\): (\d+)/', $outputString, $matches)) {
            $pid = (int)$matches[1];
            $this->waitForProcessToFinish($pid, 5);
        }
    }

    private function createTempLogFile(): string
    {
        $logFile = \sys_get_temp_dir() . '/forker_binary_test_' . \uniqid() . '.log';
        $this->processLogFiles[] = $logFile;
        return $logFile;
    }

    private function isProcessRunning(int $pid): bool
    {
        return \posix_kill($pid, 0);
    }

    private function waitForProcessToFinish(int $pid, int $timeoutSeconds): void
    {
        $startTime = \time();

        while ($this->isProcessRunning($pid) && (\time() - $startTime) < $timeoutSeconds) {
            \usleep(100000); // 0.1 секунды
        }
    }

    private function assertLogFileContainsExpectedContent(string $logFile, int $pid): void
    {
        $this->assertFileExists($logFile, 'Log file should exist');

        $logContent = \file_get_contents($logFile);
        $this->assertStringContainsString("Process {$pid} started", $logContent);
        $this->assertStringContainsString("heartbeat", $logContent);
        $this->assertStringContainsString("Process {$pid} finished", $logContent);
    }

    private function stopAllTestProcesses(): void
    {
        // Находим все процессы с именем test_process и parameter_test_process
        $output = \shell_exec('ps aux | grep -E "(test_process|parameter_test_process)" | grep -v grep | awk \'{print $2}\'');

        if ($output) {
            $pids = \array_filter(\array_map('intval', \explode("\n", \trim($output))));

            foreach ($pids as $pid) {
                if ($pid > 0 && $this->isProcessRunning($pid)) {
                    \posix_kill($pid, SIGTERM);

                    // Ждем завершения процесса
                    $this->waitForProcessTofinish($pid, 5);

                    // Если процесс все еще работает, убиваем принудительно
                    if ($this->isProcessRunning($pid)) {
                        \posix_kill($pid, SIGKILL);
                    }
                }
            }
        }

        // Очищаем временные файлы вывода
        $tempFiles = \glob('/tmp/forker_test_output_*.log');
        foreach ($tempFiles as $tempFile) {
            if (\file_exists($tempFile)) {
                \unlink($tempFile);
            }
        }
    }
}
