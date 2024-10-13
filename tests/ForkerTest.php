<?php

namespace Tests;

use Mrden\Forker\Forker;
use Mrden\Forker\Process\CallableProcess;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

final class ForkerTest extends TestCase
{
    public function testForkingProcess()
    {
        $process = new TestSingleProcess();
        $forker = new Forker($process);
        $forker->run();
        \sleep(1);
        $pid = $process->pid(1);
        $this->assertIsInt($pid);
        $this->assertTrue(\posix_kill($pid, 0));
        $forker->stop(Forker::STOP_ALL);
        \sleep(1);
        $this->assertFalse(\posix_kill($pid, 0));
    }

    public function testMultiForkingProcess()
    {
        $process = new TestSingleProcess(['time' => 3]);
        $forker = new Forker($process);
        $forker->run(3);
        \sleep(1);
        $this->assertIsInt($process->pid(1));
        $this->assertIsInt($process->pid(2));
        $this->assertIsInt($process->pid(3));
        $this->assertEquals(0, $process->pid(4));
        \sleep(3);
        $this->assertEquals(0, $process->pid(1));
        $this->assertEquals(0, $process->pid(2));
        $this->assertEquals(0, $process->pid(3));
        $forker->stop(Forker::STOP_ALL);
    }

    public function testForkingCallable()
    {
        $file = __DIR__ . '/../.mrden/callable_test.txt';
        if (\file_exists($file)) {
            \unlink($file);
        }
        $text = \date('Y-m-d H:i:s') . ': callable test text';
        $forker = new Forker(new CallableProcess(function (CallableProcess $process) use ($file, $text) {
            \file_put_contents($file, $text);
        }));
        $forker->run();
        \sleep(1);
        $this->assertStringEqualsFile($file, $text);
        $forker->stop(Forker::STOP_ALL);
    }

    public function testNotStartProcessIfExecuting()
    {
        $process = new TestSingleProcess(['time' => 25]);
        $forker1 = new Forker($process);
        $forker1->run();
        \sleep(1);
        $forker2 = new Forker($process);
        $this->assertEmpty($forker2->run());
        $forker1->stop(Forker::STOP_ALL);
        $forker2->stop(Forker::STOP_ALL);
    }

    public function testStartOneOfTwoProcessesIfOneExecuting()
    {
        $process = new TestSingleProcess(['time' => 25]);
        $forker1 = new Forker($process);
        $forker1->run();
        \sleep(1);
        $forker2 = new Forker($process);
        $this->assertCount(1, $forker2->run(2));
        $forker1->stop(Forker::STOP_ALL);
        $forker2->stop(Forker::STOP_ALL);
    }

    public function testRunNotRunningProcesses()
    {
        $process = new TestSingleProcess(['time' => 25]);
        $forker1 = new Forker($process);
        $forker1->run(2);
        \sleep(1);
        $forker2 = new Forker($process);
        $this->assertCount(4, $forker2->run(6));
        $forker1->stop(Forker::STOP_ALL);
        $forker2->stop(Forker::STOP_ALL);
    }

    public function testMaxCloneCount()
    {
        $process = new TestSingleProcess(['time' => 25]);
        $forker1 = new Forker($process);
        $attemptCount = 8;
        $this->assertNotEquals($attemptCount, $process->maxCloneCount());
        $this->assertCount($process->maxCloneCount(), $forker1->run($attemptCount));
        $forker1->stop(Forker::STOP_ALL);
    }

    public function testMultiThreadArrayDataHandlerProcess()
    {
        $countStorageFile = __DIR__ . '/../.mrden/count_read_file.txt';
        if (\file_exists($countStorageFile)) {
            \unlink($countStorageFile);
        }
        $processDataFile = __DIR__ . '/../.mrden/process_data.csv';
        if (\file_exists($processDataFile)) {
            \unlink($processDataFile);
        }
        $process = new TestMultiThreadArrayDataHandleProcess();
        $forker = new Forker($process);
        $forker->run();
        \sleep(10);
        $forker->stop(Forker::STOP_ALL);

        $currentCount = 0;
        if (\file_exists($countStorageFile)) {
            $currentCount = (int) \file_get_contents($countStorageFile);
        }

        $this->assertEquals(1, $currentCount);
    }
}
