<?php

namespace Mrden\Fork\Contracts;

/**
 * @template T of mixed
 */
abstract class MultiThreadProcess extends Process implements SpecificCountCloneable, DataPreparable
{
    protected $maxCloneCount = 16;

    /**
     * @psalm-var list<T>
     */
    protected $data = [];
    protected $totalCountData = 0;
    protected $ncpu = 2;

    protected function execute(): void
    {
        for ($i = $this->runningCloneNumber - 1; $i < $this->totalCountData; $i += $this->countOfClones()) {
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
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $this->ncpu = count($matches[0]);
        } else {
            $ncpu = shell_exec('nproc');
            $this->ncpu = $ncpu ?: $this->ncpu;
        }
    }

    public function countOfClones(): int
    {
        return min($this->maxCloneCount, $this->ncpu);
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
