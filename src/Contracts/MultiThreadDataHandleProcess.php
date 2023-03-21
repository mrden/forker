<?php

namespace Mrden\Fork\Contracts;

/**
 * @template T of mixed
 */
abstract class MultiThreadDataHandleProcess extends Process implements SpecificCountCloneable, DataPreparable
{
    /**
     * @psalm-var positive-int
     */
    protected $maxCloneCount = 16;

    /**
     * @psalm-var list<T>
     */
    private $data = [];
    private $totalCountData = 0;
    private $countCpu = 2;

    protected function execute(): void
    {
        for ($i = $this->getRunningCloneNumber() - 1; $i < $this->totalCountData; $i += $this->countOfClones()) {
            $data = $this->data[$i] ?? null;
            if ($data) {
                $this->dataHandler($i, $data);
            }
        }
    }

    public function prepareData(): void
    {
        $this->data = $this->data();
        $this->totalCountData = count($this->data);

        // Count logical processors
        if (\is_file('/proc/cpuinfo')) {
            $cpuInfo = \file_get_contents('/proc/cpuinfo');
            \preg_match_all('/^processor/m', $cpuInfo, $matches);
            $this->countCpu = count($matches[0]);
        } else {
            $nCpu = (int)\shell_exec('nproc');
            $this->countCpu = $nCpu ?: $this->countCpu;
        }
    }

    public function countOfClones(): int
    {
        return min($this->maxCloneCount, $this->countCpu);
    }

    /**
     * @psalm-return list<T>
     */
    abstract protected function data(): array;

    /**
     * @psalm-param positive-int $keyItem
     * @psalm-param T $dataItem
     */
    abstract protected function dataHandler(int $keyItem, $dataItem): void;
}
