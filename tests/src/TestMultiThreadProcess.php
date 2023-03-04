<?php

namespace Tests\src;


use Mrden\Fork\Contracts\MultiThreadProcess;
use Tests\src\Traits\ProcessFileStorageTrait;

class TestMultiThreadProcess extends MultiThreadProcess
{
    use ProcessFileStorageTrait;

    protected $maxCloneCount = 1;

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
        if (!$dataItem) {
            return;
        }
        $file = __DIR__ . '/../storage/process_data.csv';
        file_put_contents($file, implode(';', [
            'CPU (' . $this->ncpu . ')',
            $keyItem,
            $this->runningCloneNumber,
            (new \DateTime())->format('m-d-Y H:i:s.u'),
            $dataItem['posting_number'],
            $dataItem['status'],
        ]) . \PHP_EOL, FILE_APPEND);
    }
}
