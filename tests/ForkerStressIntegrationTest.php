<?php

namespace Tests;

use Mrden\Forker\Forker;
use Mrden\Forker\ProcessManager\PosixProcessManager;
use PHPUnit\Framework\TestCase;
use Tests\Integration\TestProcess;

/**
 * Стресс-тесты для проверки надежности под нагрузкой
 *
 * @group stress
 * @group integration
 */
class ForkerStressIntegrationTest extends TestCase
{
    private array $createdProcesses = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (PHP_SAPI !== 'cli' || !extension_loaded('pcntl') || !extension_loaded('posix')) {
            $this->markTestSkipped('Stress tests require CLI mode with PCNTL and POSIX extensions');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->createdProcesses as $process) {
            if ($process instanceof TestProcess) {
                $process->cleanup();
            }
        }
        $this->createdProcesses = [];

        parent::tearDown();
    }

    /**
     * Тест множественных быстрых перезапусков
     */
    public function testRapidRestarts(): void
    {
        $process = new TestProcess(['run_duration' => 10]);
        $processManager = new PosixProcessManager();
        $this->createdProcesses[] = $process;

        $forker = new Forker($process, $processManager);

        $allPids = [];

        // Выполняем 5 перезапусков
        for ($i = 0; $i < 5; $i++) {
            $pids = $forker->restart(1, null, 5);
            $pid = $pids[0];

            $this->assertIsInt($pid);
            $this->assertGreaterThan(0, $pid);
            $this->assertTrue(posix_kill($pid, 0), "Process {$pid} should be running after restart {$i}");

            $allPids[] = $pid;
        }

        // Все PID должны быть разными
        $this->assertCount(5, array_unique($allPids), 'All restart PIDs should be unique');

        // Останавливаем финальный процесс
        $finalPid = end($allPids);
        $forker->stop(1);

        $this->assertTrue($processManager->waitForProcessStop($finalPid, 15));
        $this->assertFalse(posix_kill($finalPid, 0), 'Final process should be stopped');
    }

    /**
     * Тест работы с максимальным количеством процессов
     */
    public function testMaxProcesses(): void
    {
        $process = new TestProcess(['run_duration' => 10]);
        $process->setMaxCloneCount(5);
        $this->createdProcesses[] = $process;

        $forker = new Forker($process);

        // Запускаем максимальное количество процессов
        $pids = $forker->run(5);

        $this->assertCount(5, $pids);

        // Проверяем, что все процессы запущены
        foreach ($pids as $i => $pid) {
            $this->assertTrue(posix_kill($pid, 0), "Process {$pid} should be running");
            $storedPid = $process->pid($i + 1);
            $this->assertEquals($pid, $storedPid);
        }

        // Перезапускаем все процессы
        $newPids = $forker->restart(5);

        $this->assertCount(5, $newPids);

        // Проверяем, что старые процессы остановлены, новые запущены
        foreach ($pids as $oldPid) {
            $this->assertFalse(posix_kill($oldPid, 0), "Old process {$oldPid} should be stopped");
        }

        foreach ($newPids as $newPid) {
            $this->assertTrue(posix_kill($newPid, 0), "New process {$newPid} should be running");
        }

        // Все новые PID должны отличаться от старых
        $this->assertEmpty(array_intersect($pids, $newPids), 'New PIDs should be different from old ones');

        // Очистка
        $forker->stopAll();
    }

    /**
     * Тест обработки принудительного завершения (SIGKILL)
     */
    public function testForcedTermination(): void
    {
        // Создаем процесс, который игнорирует SIGUSR1
        $process = new class (['run_duration' => 30]) extends TestProcess {
            protected function execute(): void
            {
                $startTime = time();
                $pid = getmypid();

                file_put_contents($this->logFile, "Stubborn process {$pid} started\n", FILE_APPEND);

                // Игнорируем сигнал остановки
                $this->processManager->setSignalHandler(SIGUSR1, SIG_IGN);

                while ((time() - $startTime) < $this->runDuration) {
                    file_put_contents($this->logFile, "Stubborn process {$pid} still running\n", FILE_APPEND);
                    sleep(1);
                }
            }
        };

        $this->createdProcesses[] = $process;
        $processManager = new PosixProcessManager();
        $forker = new Forker($process, $processManager);

        $pids = $forker->run();
        $pid = $pids[0];

        sleep(1);

        // Перезапуск с коротким таймаутом должен привести к SIGKILL
        $startTime = microtime(true);
        $newPids = $forker->restart(1, null, 2); // 2 секунды таймаут
        $endTime = microtime(true);

        $newPid = $newPids[0];

        // Перезапуск должен завершиться за разумное время (максимум 6 секунд с запасом)
        $this->assertLessThan(6, $endTime - $startTime, 'Forced termination should complete within reasonable time');

        // Старый процесс должен быть убит
        $this->assertFalse($processManager->isProcessRunning($pid), 'Stubborn process should be force-killed');

        // Новый процесс должен работать
        $this->assertTrue($processManager->isProcessRunning($newPid), 'New process should be running');

        // Очистка
        $forker->stop(1);
        $this->assertTrue($processManager->waitForProcessStop($newPid, 5));
    }
}
