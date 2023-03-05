<?php

namespace Tests\src;

use Mrden\Fork\Contracts\MultiThreadDataHandleProcess;
use Tests\src\Traits\ProcessFileStorageTrait;

/**
 * @template-extends MultiThreadDataHandleProcess<array{posting_number: string, status: string}>
 */
class TestMultiThreadDataHandleProcess extends MultiThreadDataHandleProcess
{
    use ProcessFileStorageTrait;

    protected $maxCloneCount = 6;

    protected function checkParams(): void
    {
    }

    protected function prepare(): void
    {
    }

    protected function data(): array
    {
        return array_values(json_decode(file_get_contents(__DIR__ . '/jsonList.json'), true));
    }

    protected function dataHandler(int $keyItem, $dataItem): void
    {
        $file = __DIR__ . '/../storage/process_data.csv';
        file_put_contents($file, implode(';', [
            'CPU (' . $this->countCpu . ')',
            $keyItem,
            $this->runningCloneNumber,
            (new \DateTime())->format('m-d-Y H:i:s.u'),
            $dataItem['posting_number'],
            $dataItem['status'],
        ]) . \PHP_EOL, FILE_APPEND);
    }
}
