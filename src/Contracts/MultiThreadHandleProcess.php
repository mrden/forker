<?php

namespace Mrden\Forker\Contracts;

use Mrden\Forker\Helpers\SysInfo;

/**
 * @template T of mixed
 */
abstract class MultiThreadHandleProcess extends Process implements SpecificCountCloneable, Preparable
{
    /**
     * @psalm-var positive-int
     */
    protected int $maxCloneCount = 16;

    /**
     * @psalm-var int<1, max>
     */
    private int $countCpu;

    /**
     * @psalm-var iterable<T>
     */
    private iterable $data = [];

    /**
     * @throws \Exception
     */
    public function __construct(
        array $params = [],
        ?ProcessManagerInterface $processManager = null,
        ?string $pidStorageClassName = null
    ) {
        parent::__construct($params, $processManager, $pidStorageClassName);
        $this->countCpu = SysInfo::numCpu() ?? 2;
    }

    public function prepareToFork(): void
    {
        $this->data = $this->data();
    }

    protected function execute(): void
    {
        $index = 1;
        $nextHandleIndex = $this->getRunningCloneNumber();
        foreach ($this->data as $dataItem) {
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
