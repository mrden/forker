<?php

namespace Tests;

use Mrden\Forker\Process\CallableProcess;
use PHPUnit\Framework\TestCase;

class CallableProcessIdTest extends TestCase
{
    public function testIdBasedOnClosureLocation(): void
    {
        // Создаем два процесса с замыканиями в одном месте
        $process1 = new CallableProcess(function () { echo "Hello"; });
        $process2 = new CallableProcess(function () { echo "Hello"; });

        // ID должны быть одинаковыми, так как замыкания определены в одном месте
        $this->assertEquals($process1->id(), $process2->id());
    }

    public function testIdDifferentForDifferentLocations(): void
    {
        // Замыкание в одном месте
        $closure1 = function () { echo "Hello"; };
        $process1 = new CallableProcess($closure1);

        // Замыкание в другом месте (другая строка)
        $closure2 = function () { echo "World"; };
        $process2 = new CallableProcess($closure2);

        // ID должны быть разными, так как замыкания определены в разных местах
        $this->assertNotEquals($process1->id(), $process2->id());
    }

    public function testIdConsistentAcrossMultipleCalls(): void
    {
        $process = new CallableProcess(function () { echo "Test"; });

        $id1 = $process->id();
        $id2 = $process->id();
        $id3 = $process->id();

        // ID должен быть стабильным при множественных вызовах
        $this->assertEquals($id1, $id2);
        $this->assertEquals($id2, $id3);
    }

    public function testIdIncludesParameters(): void
    {
        $closure = function () { echo "Test"; };

        $process1 = new CallableProcess($closure, []);
        $process2 = new CallableProcess($closure, ['param' => 'value1']);
        $process3 = new CallableProcess($closure, ['param' => 'value2']);

        // Процессы с разными параметрами должны иметь разные ID
        $this->assertNotEquals($process1->id(), $process2->id());
        $this->assertNotEquals($process2->id(), $process3->id());
        $this->assertNotEquals($process1->id(), $process3->id());
    }

    public function testIdWithSameParameters(): void
    {
        $closure = function () { echo "Test"; };

        $process1 = new CallableProcess($closure, ['param' => 'value']);
        $process2 = new CallableProcess($closure, ['param' => 'value']);

        // Процессы с одинаковыми параметрами должны иметь одинаковый ID
        $this->assertEquals($process1->id(), $process2->id());
    }

    public function testIdIsValidMd5Hash(): void
    {
        $process = new CallableProcess(function () { echo "Test"; });
        $id = $process->id();

        // ID должен быть валидным MD5 хешем (32 символа, hex)
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $id);
    }

    public function testIdWithComplexParameters(): void
    {
        $closure = function () { echo "Complex test"; };

        $complexParams = [
            'string' => 'test string',
            'number' => 42,
            'array' => ['nested' => 'value'],
            'bool' => true,
            'null' => null,
        ];

        $process1 = new CallableProcess($closure, $complexParams);
        $process2 = new CallableProcess($closure, $complexParams);

        // Процессы со сложными, но одинаковыми параметрами должны иметь одинаковый ID
        $this->assertEquals($process1->id(), $process2->id());

        // Изменим один параметр
        $modifiedParams = $complexParams;
        $modifiedParams['string'] = 'modified';

        $process3 = new CallableProcess($closure, $modifiedParams);

        // ID должен измениться
        $this->assertNotEquals($process1->id(), $process3->id());
    }

    public function testIdWithClosureFromDifferentFiles(): void
    {
        // Симулируем замыкания из разных "файлов" создавая их в разных методах
        $process1 = $this->createProcessFromMethod1();
        $process2 = $this->createProcessFromMethod2();

        // ID должны быть разными, так как замыкания из разных мест
        $this->assertNotEquals($process1->id(), $process2->id());
    }

    private function createProcessFromMethod1(): CallableProcess
    {
        return new CallableProcess(function () { echo "Method 1"; });
    }

    private function createProcessFromMethod2(): CallableProcess
    {
        return new CallableProcess(function () { echo "Method 2"; });
    }

    public function testIdWithVariableCapture(): void
    {
        $capturedVar = 'test';

        $process1 = new CallableProcess(function () use ($capturedVar) {
            echo $capturedVar;
        });

        $process2 = new CallableProcess(function () use ($capturedVar) {
            echo $capturedVar;
        });
        $this->assertEquals($process1->id(), $process2->id());
    }

    public function testIdStabilityAcrossInstances(): void
    {
        // Создаем замыкание в отдельной переменной
        $sharedClosure = function () { echo "Shared logic"; };

        $process1 = new CallableProcess($sharedClosure, ['env' => 'test']);
        $process2 = new CallableProcess($sharedClosure, ['env' => 'test']);

        // Процессы с одним и тем же замыканием и параметрами должны иметь одинаковый ID
        $this->assertEquals($process1->id(), $process2->id());

        // Создаем новые процессы в другом запуске
        unset($process1, $process2);

        $process3 = new CallableProcess($sharedClosure, ['env' => 'test']);
        $process4 = new CallableProcess($sharedClosure, ['env' => 'test']);

        $this->assertEquals($process3->id(), $process4->id());
    }
}
