<?php

namespace Tests;

use Mrden\Fork\Forker;
use Tests\src\TestDaemonProcess;
use Tests\src\TestSingleProcess;

final class ForkerTest extends \PHPUnit\Framework\TestCase
{
    public function testException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Incorrect process realization (must be callable or \Mrden\Fork\Contracts\Forkable).');
        new Forker('string');
    }

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
        $file = __DIR__ . '/storage/callable_test.txt';
        if (\file_exists($file)) {
            \unlink($file);
        }
        $text = 'callable test text';
        $forker = new Forker(function () use ($file, $text) {
            \file_put_contents($file, $text);
        });
        $forker->run();
        \sleep(1);
        $this->assertStringEqualsFile($file, $text);
        $forker->stop(Forker::STOP_ALL);
    }

    public function testRestorePidInStorage()
    {
        $process = new TestDaemonProcess(['test-param' => 50]);
        $forker = new Forker($process);
        $forker->run();
        $pidFile = __DIR__ . '/storage/forker/a057668c-c305-5815-ab57-a69c2ba5197b/1.pid';
        \sleep(1);
        if (\file_exists($pidFile)) {
            \unlink($pidFile);
        }
        $this->assertTrue($process->pid(1) == 0);
        \sleep(5);
        $this->assertTrue($process->pid(1) > 0);
        $forker->stop(Forker::STOP_ALL);
        \sleep(3);
    }
}