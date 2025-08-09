<?php

namespace Tests;

use Mrden\Forker\Forker;
use Mrden\Forker\ProcessManager\PosixProcessManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Integration\TestProcess;

#[Group("integration")]
#[Group("stress")]
class ForkerStressIntegrationTest extends TestCase
{
    private array $createdProcesses = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (PHP_SAPI !== 'cli' || !\extension_loaded('pcntl') || !\extension_loaded('posix')) {
            $this->markTestSkipped('Stress tests require CLI mode with PCNTL and POSIX extensions');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->createdProcesses as $process) {
            if ($process instanceof TestProcess) {
                $process->cleanupLogFile();
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
            $this->assertTrue($processManager->isProcessRunning($pid), "Process {$pid} should be running after restart {$i}");

            $allPids[] = $pid;
        }

        // Все PID должны быть разными
        $this->assertCount(5, \array_unique($allPids), 'All restart PIDs should be unique');

        // Останавливаем финальный процесс
        $finalPid = \end($allPids);
        $forker->stop(1);

        $this->assertTrue($processManager->waitForProcessStop($finalPid, 15));
        $this->assertFalse($processManager->isProcessRunning($finalPid), 'Final process should be stopped');
    }

    /**
     * Тест работы с максимальным количеством процессов
     */
    public function testMaxProcesses(): void
    {
        $process = new TestProcess(['run_duration' => 10]);
        $processManager = new PosixProcessManager();
        $processCount = 5;
        $process->setMaxCloneCount($processCount);
        $this->createdProcesses[] = $process;

        $forker = new Forker($process, $processManager);

        // Запускаем максимальное количество процессов
        $pids = $forker->run($processCount);

        $this->assertCount($processCount, $pids);

        // Проверяем, что все процессы запущены
        foreach ($pids as $i => $pid) {
            $this->assertTrue($processManager->isProcessRunning($pid), "Process {$pid} should be running");
            $storedPid = $process->pid($i + 1);
            $this->assertEquals($pid, $storedPid);
        }

        // Перезапускаем все процессы
        $newPids = $forker->restart($processCount);

        $this->assertCount($processCount, $newPids);
        $this->assertNotEquals($pids, $newPids);

        // Проверяем, что старые процессы остановлены, новые запущены
        foreach ($pids as $oldPid) {
            $this->assertFalse($processManager->isProcessRunning($oldPid), "Old process {$oldPid} should be stopped");
        }

        foreach ($newPids as $newPid) {
            $this->assertTrue($processManager->isProcessRunning($newPid), "New process {$newPid} should be running");
        }

        // Все новые PID должны отличаться от старых
        $this->assertEmpty(\array_intersect($pids, $newPids), 'New PIDs should be different from old ones');

        // Очистка
        $forker->stopAll();
    }

    /**
     * Тест обработки принудительного завершения (SIGKILL)
     */
    public function testForcedTermination(): void
    {
        // Создаем процесс, который игнорирует SIGTERM
        $process = new class (['run_duration' => 30]) extends TestProcess {
            protected function execute(): void
            {
                $startTime = \time();
                $pid = \getmypid();

                \file_put_contents($this->logFile, "Stubborn process {$pid} started\n", FILE_APPEND);

                // Игнорируем сигнал остановки
                $this->processManager->setSignalHandler(SIGTERM, SIG_IGN);

                while ((\time() - $startTime) < $this->runDuration) {
                    \file_put_contents($this->logFile, "Stubborn process {$pid} still running\n", FILE_APPEND);
                    \sleep(1);
                }
            }
        };

        $this->createdProcesses[] = $process;
        $processManager = new PosixProcessManager();
        $forker = new Forker($process, $processManager);

        $pids = $forker->run();
        $pid = $pids[0];

        \sleep(1);

        // Перезапуск с коротким таймаутом должен привести к SIGKILL
        $startTime = \microtime(true);
        $newPids = $forker->restart(1, null, 1, true); // 2 секунды таймаут
        $endTime = \microtime(true);

        $newPid = $newPids[0];

        // Перезапуск должен завершиться за разумное время (максимум 6 секунд с запасом)
        $this->assertLessThan(6, $endTime - $startTime, 'Forced termination should complete within reasonable time');

        // Старый процесс должен быть убит
        $this->assertFalse($processManager->isProcessRunning($pid), 'Stubborn process should be force-killed');

        // Новый процесс должен работать
        $this->assertTrue($processManager->isProcessRunning($newPid), 'New process should be running');

        // Очистка
        $stopPids = $forker->stop(1, null, 1, true);
        $this->assertEquals([$newPid], $stopPids);
        $this->assertTrue($processManager->waitForProcessStop($newPid, 5));
    }
}
