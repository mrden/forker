<?php

namespace Tests;

use Mrden\Forker\Contracts\MultiThreadHandleProcess;
use Mrden\Forker\Contracts\Storage;
use Mrden\Forker\Storage\FileStorage;
use Mrden\Forker\Traits\ProcessFileStorageTrait;

/**
 * @template-extends MultiThreadHandleProcess<iterable{posting_number: string, status: string}>
 */
class TestMultiThreadArrayDataHandleProcess extends MultiThreadHandleProcess
{
    use ProcessFileStorageTrait;

    /**
     * @psalm-var positive-int
     */
    protected $maxCloneCount = 12;

    protected function checkParams(): void
    {
    }

    protected function prepare(): void
    {
    }

    protected function data(): iterable
    {
        \sleep(1);
        $countStorageFile = __DIR__ . '/../.mrden/count_read_file.txt';
        $currentCount = 0;
        if (\file_exists($countStorageFile)) {
            $currentCount = (int) \file_get_contents($countStorageFile);
        }
        \file_put_contents($countStorageFile, $currentCount + 1, \LOCK_EX);
        return \array_values(\json_decode(\file_get_contents(__DIR__ . '/jsonList.json'), true));
    }

    protected function dataHandler(int $key, $data): void
    {
        $file = __DIR__ . '/../.mrden/process_data.csv';
        \file_put_contents($file, \implode(';', [
            $key,
            'process-n-' . $this->getRunningCloneNumber(),
            (new \DateTime())->format('m-d-Y H:i:s.u'),
            $data['posting_number'],
            $data['status'],
        ]) . \PHP_EOL, FILE_APPEND | \LOCK_EX);
    }

    protected function pidStorage(): Storage
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FileStorage($this, __DIR__ . '/../.mrden');
        }
        return $this->pidStorage;
    }
}
