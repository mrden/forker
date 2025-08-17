<?php

namespace Tests;

use Mrden\Forker\Forker;
use PHPUnit\Framework\TestCase;
use Tests\Mock\MockProcess;

class ForkerMockTest extends TestCase
{
    private MockProcess $mockProcess;

    protected function setUp(): void
    {
        $this->mockProcess = new MockProcess();
    }

    public function testRunProcess(): void
    {
        $forker = new Forker($this->mockProcess);
        $pids = $forker->run();

        // Проверяем, что был создан один процесс
        $this->assertCount(1, $pids);
        $this->assertEquals(1001, $pids[0]);

        // Проверяем, что процесс отмечен как работающий
        $this->assertTrue($this->mockProcess->getProcessManager()->isProcessRunning($pids[0]));

        // Проверяем, что PID был сохранен в процессе
        $this->assertEquals($pids[0], $this->mockProcess->getPidStorage()->get(1));
    }

    public function testRunMultipleProcesses(): void
    {
        $this->mockProcess->setMaxCloneCount(3);
        $forker = new Forker($this->mockProcess);
        $pids = $forker->run(3);

        // Проверяем, что были созданы три процесса
        $this->assertCount(3, $pids);

        // Проверяем, что все процессы отмечены как работающие
        foreach ($pids as $pid) {
            $this->assertTrue($this->mockProcess->getProcessManager()->isProcessRunning($pid));
        }

        // Проверяем, что PID были сохранены в процессе
        $this->assertEquals($pids[0], $this->mockProcess->getPidStorage()->get(1));
        $this->assertEquals($pids[1], $this->mockProcess->getPidStorage()->get(2));
        $this->assertEquals($pids[2], $this->mockProcess->getPidStorage()->get(3));
    }

    public function testStopProcess(): void
    {
        $forker = new Forker($this->mockProcess);
        $pids = $forker->run();

        $this->assertTrue($this->mockProcess->getProcessManager()->isProcessRunning($pids[0]));

        $stoppedPids = $forker->stop(1);

        $this->assertEquals($pids, $stoppedPids);
        $this->assertFalse($this->mockProcess->getProcessManager()->isProcessRunning($pids[0]));
    }


    public function testRestartProcess(): void
    {
        $forker = new Forker($this->mockProcess);
        $originalPids = $forker->run();

        // Проверяем, что процесс запущен
        $this->assertTrue($this->mockProcess->getProcessManager()->isProcessRunning($originalPids[0]));

        // Перезапускаем процесс - теперь это останавливает старый и создает новый
        $newPids = $forker->restart(1);

        // Проверяем, что создан новый процесс с новым PID
        $this->assertNotEquals($originalPids[0], $newPids[0]);
        $this->assertTrue($this->mockProcess->getProcessManager()->isProcessRunning($newPids[0]));
        $this->assertFalse($this->mockProcess->getProcessManager()->isProcessRunning($originalPids[0]));
    }


    public function testChildProcessSimulation(): void
    {
        // Устанавливаем режим эмуляции дочернего процесса
        $this->mockProcess->getProcessManager()->emulateChildProcess(true);
        $forker = new Forker($this->mockProcess);

        // Перехватываем exit() в дочернем процессе
        $this->mockProcess->setExitCallback(function (): void {
            throw new \RuntimeException('Process exit was called');
        });

        // В режиме дочернего процесса должен вызываться exit()
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Process exit was called');

        $forker->run();
    }

    public function testNonCliMode(): void
    {
        // Устанавливаем не-CLI режим
        $this->mockProcess->getProcessManager()->setCliMode(false);

        // В не-CLI режиме конструктор Forker должен выбросить исключение
        $this->expectException(\Mrden\Forker\Exceptions\ForkException::class);
        $this->expectExceptionMessage('Forker is only used in cli mode.');

        new Forker($this->mockProcess);
    }
}
