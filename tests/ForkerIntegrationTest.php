<?php

namespace Tests;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Forker;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Integration\TestProcess;

#[Group("integration")]
class ForkerIntegrationTest extends TestCase
{
    /**
     * @var list<Process>
     */
    private array $createdProcesses = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Проверяем, что мы в CLI и есть поддержка pcntl
        if (PHP_SAPI !== 'cli') {
            $this->markTestSkipped('Integration tests can only run in CLI mode');
        }

        if (!\extension_loaded('pcntl')) {
            $this->markTestSkipped('PCNTL extension is required for integration tests');
        }

        if (!\extension_loaded('posix')) {
            $this->markTestSkipped('POSIX extension is required for integration tests');
        }
    }

    protected function tearDown(): void
    {
        // Очищаем созданные процессы
        foreach ($this->createdProcesses as $process) {
            if ($process instanceof TestProcess) {
                $process->cleanupLogFile();
            }
        }
        $this->createdProcesses = [];

        parent::tearDown();
    }

    public function testBasicForkAndStop(): void
    {
        $process = new TestProcess(['run_duration' => 30]); // Долгий процесс
        $this->createdProcesses[] = $process;

        $forker = new Forker($process);

        // Запускаем процесс
        $pids = $forker->run();

        $this->assertCount(1, $pids);
        $this->assertIsInt($pids[0]);
        $this->assertGreaterThan(0, $pids[0]);

        // Проверяем, что процесс действительно запущен
        $pid = $process->getPidStorage()->get(1);
        $this->assertEquals($pids[0], $pid);
        $this->assertTrue($process->getProcessManager()->isProcessRunning($pid), 'Process should be running');

        // Ждем немного, чтобы процесс успел записать в лог
        \sleep(2);

        // Проверяем лог-файл
        $logContents = $process->getLogContents();
        $this->assertStringContainsString("Process {$pid} started", $logContents);
        $this->assertStringContainsString("heartbeat", $logContents);

        // Останавливаем процесс
        $stoppedPids = $forker->stop(1);
        $this->assertEquals($pids, $stoppedPids);

        // Проверяем, что процесс завершился
        $this->assertTrue($process->getProcessManager()->waitForProcessStop($pid, 5), 'Process should be stopped');

        // Проверяем лог завершения
        $finalLog = $process->getLogContents();
        $this->assertStringContainsString("Process {$pid} finished", $finalLog);
    }

    public function testMultipleProcesses(): void
    {
        $process = new TestProcess(['run_duration' => 30]);
        $this->createdProcesses[] = $process;

        $forker = new Forker($process);

        // Запускаем 3 процесса
        $pids = $forker->run(3);

        $this->assertCount(3, $pids);

        // Проверяем, что все процессы запущены
        foreach ($pids as $i => $pid) {
            $this->assertIsInt($pid);
            $this->assertGreaterThan(0, $pid);
            $this->assertTrue($process->getProcessManager()->isProcessRunning($pid), "Process {$pid} should be running");

            // Проверяем, что PID корректно сохранен
            $storedPid = $process->getPidStorage()->get($i + 1);
            $this->assertEquals($pid, $storedPid);
        }

        // Проверяем, что все PID разные
        $this->assertCount(3, \array_unique($pids));

        $stoppedPids = $forker->stopAll();
        $this->assertCount(3, $stoppedPids);

        // Проверяем, что все процессы остановлены
        foreach ($pids as $pid) {
            $this->assertTrue($process->getProcessManager()->waitForProcessStop($pid, 15), "Process {$pid} should be stopped");
        }
    }

    public function testRestartProcess(): void
    {
        $process = new TestProcess(['run_duration' => 30]);
        $this->createdProcesses[] = $process;

        $forker = new Forker($process);

        // Запускаем процесс
        $originalPids = $forker->run();
        $originalPid = $originalPids[0];

        $this->assertTrue($process->getProcessManager()->isProcessRunning($originalPid), 'Original process should be running');

        // Перезапускаем процесс
        $newPids = $forker->restart(1);
        $newPid = $newPids[0];

        $this->assertNotEquals($originalPid, $newPid, 'New process should have different PID');

        // Проверяем, что старый процесс остановлен, а новый запущен
        $this->assertTrue($process->getProcessManager()->waitForProcessStop($originalPid, 1), 'Original process should be stopped');
        $this->assertTrue($process->getProcessManager()->isProcessRunning($newPid), 'New process should be running');

        // Проверяем, что PID обновился в storage
        $storedPid = $process->getPidStorage()->get(1);
        $this->assertEquals($newPid, $storedPid);

        // Останавливаем новый процесс
        $forker->stop(1);
        $this->assertTrue($process->getProcessManager()->waitForProcessStop($newPid, 15), 'New process should be stopped');
    }

    public function testProcessCrashHandling(): void
    {
        $process = new TestProcess(['run_duration' => 2]); // Короткий процесс
        $this->createdProcesses[] = $process;

        $forker = new Forker($process);

        $pids = $forker->run();
        $pid = $pids[0];

        $this->assertTrue($process->getProcessManager()->isProcessRunning($pid), 'Process should be running');

        // Процесс должен завершиться сам
        $this->assertTrue($process->getProcessManager()->waitForProcessStop($pid, 3), 'Process should have finished naturally');

        // Попытка перезапуска должна создать новый процесс
        $newPids = $forker->restart(1);
        $newPid = $newPids[0];

        $this->assertNotEquals($pid, $newPid);
        $this->assertTrue($process->getProcessManager()->isProcessRunning($newPid), 'New process should be running');

        // Очистка
        $forker->stop(1);
        $this->assertTrue($process->getProcessManager()->waitForProcessStop($newPid, 15), 'New process should be stopped');
    }

    public function testSignalHandling(): void
    {
        $process = new TestProcess(['run_duration' => 30]);
        $this->createdProcesses[] = $process;

        $forker = new Forker($process);

        $pids = $forker->run();
        $pid = $pids[0];

        // Просим процесс завершиться
        $this->assertTrue($process->getProcessManager()->requestGracefulShutdown($pid));
        // Процесс должен завершиться
        $this->assertTrue($process->getProcessManager()->waitForProcessStop($pid, 5), 'Process should respond to SIGTERM');

        // Проверяем лог
        $logContents = $process->getLogContents();
        $this->assertStringContainsString("finished", $logContents);
    }

    public function testWaitForProcessStop(): void
    {
        $process = new TestProcess(['run_duration' => 30]);
        $this->createdProcesses[] = $process;

        $forker = new Forker($process);

        $pids = $forker->run();
        $pid = $pids[0];

        // Засекаем время перезапуска
        $startTime = \microtime(true);

        $newPids = $forker->restart(1, null, 5); // 5 секунд таймаут

        $endTime = \microtime(true);
        $restartTime = $endTime - $startTime;

        // Перезапуск должен занять разумное время (не больше 7 секунд с запасом)
        $this->assertLessThan(7, $restartTime, 'Restart should complete within timeout');

        $newPid = $newPids[0];
        $this->assertNotEquals($pid, $newPid);
        $this->assertTrue($process->getProcessManager()->waitForProcessStop($pid, 5));

        // Очистка
        $forker->stop(1);
        $this->assertTrue($process->getProcessManager()->waitForProcessStop($newPid, 5), 'New process should be stopped');
    }

    public function testProcessMemoryAndCleanup(): void
    {
        $process = new TestProcess(['run_duration' => 5]);
        $this->createdProcesses[] = $process;

        $forker = new Forker($process);

        // Запускаем и сразу останавливаем несколько раз
        for ($i = 0; $i < 3; $i++) {
            $pids = $forker->run();
            $forker->stop(1);

            // Активно ждём завершения процессов
            foreach ($pids as $pid) {
                $process->getProcessManager()->waitForProcessStop($pid, 10); // Максимум 10 секунд ожидания
                $this->assertFalse($process->getProcessManager()->isProcessRunning($pid), "Process {$pid} should be cleaned up");
            }
        }

        // Проверяем, что нет зомби-процессов
        $this->assertZombieProcessesCleanedUp();
    }

    /**
     * Проверяет отсутствие зомби-процессов
     */
    private function assertZombieProcessesCleanedUp(): void
    {
        // Получаем список процессов с нашим именем
        $output = \shell_exec('ps aux | grep "test_process" | grep -v grep | wc -l');
        $processCount = (int) \trim($output);

        // Не должно быть зависших процессов
        $this->assertEquals(0, $processCount, 'No hanging test processes should remain');
    }
}
