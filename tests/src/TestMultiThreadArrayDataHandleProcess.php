<?php

namespace Tests\src;

use Mrden\Fork\Contracts\MultiThreadHandleProcess;
use Tests\src\Traits\ProcessFileStorageTrait;

/**
 * @template-extends MultiThreadHandleProcess<iterable{posting_number: string, status: string}>
 */
class TestMultiThreadArrayDataHandleProcess extends MultiThreadHandleProcess
{
    use ProcessFileStorageTrait;

    /**
     * @psalm-var positive-int
     */
    protected $maxCloneCount = 6;

    protected function checkParams(): void
    {
    }

    protected function prepare(): void
    {
    }

    protected function data(): iterable
    {
        return array_values(json_decode(file_get_contents(__DIR__ . '/jsonList.json'), true));
    }

    protected function dataHandler(int $key, $data): void
    {
        $file = __DIR__ . '/../storage/process_data.csv';
        file_put_contents($file, implode(';', [
            $key,
            $this->getRunningCloneNumber(),
            (new \DateTime())->format('m-d-Y H:i:s.u'),
            $data['posting_number'],
            $data['status'],
        ]) . \PHP_EOL, FILE_APPEND);
    }
}
