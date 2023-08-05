<?php

namespace Mrden\Fork\Contracts;

/**
 * @template T of mixed
 */
abstract class MultiThreadHandleProcess extends Process implements SpecificCountCloneable
{
    /**
     * @psalm-var positive-int
     */
    protected $maxCloneCount = 16;

    /**
     * @var int
     */
    private $countCpu = 2;

    public function __construct(array $params = [], ?Process $parentProcess = null)
    {
        parent::__construct($params, $parentProcess);
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

    protected function execute(): void
    {
        $index = 1;
        $nextHandleIndex = $this->getRunningCloneNumber();
        foreach ($this->data() as $dataItem) {
            if ($dataItem && $nextHandleIndex == $index) {
                $this->dataHandler($index, $dataItem);
                $nextHandleIndex = $index + $this->countOfClones();
            }
            $index++;
        }
    }

    public function countOfClones(): int
    {
        return min($this->maxCloneCount, $this->countCpu);
    }

    /**
     * @psalm-return iterable<T>
     */
    abstract protected function data(): iterable;

    /**
     * @psalm-param positive-int $keyItem
     * @psalm-param T $dataItem
     */
    abstract protected function dataHandler(int $keyItem, $dataItem): void;
}
