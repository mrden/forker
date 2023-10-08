<?php

namespace Mrden\Fork\Contracts;

use Mrden\Fork\Contracts\Interfaces\Parental;
use Mrden\Fork\Contracts\Interfaces\SpecificCountCloneable;
use Mrden\Fork\Helpers\SysInfo;

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
     * @psalm-var positive-int
     */
    private $countCpu;

    /**
     * @throws \Exception
     */
    public function __construct(array $params = [], ?Parental $parentProcess = null)
    {
        parent::__construct($params, $parentProcess);
        $this->countCpu = SysInfo::numCpu() ?? 2;
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
        return \min($this->maxCloneCount, $this->countCpu);
    }

    /**
     * @psalm-return iterable<T>
     */
    abstract protected function data(): iterable;

    /**
     * @psalm-param positive-int $key
     * @psalm-param T $data
     */
    abstract protected function dataHandler(int $key, $data): void;
}
