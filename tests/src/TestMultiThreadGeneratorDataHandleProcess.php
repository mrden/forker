<?php

namespace Tests\src;

/**
 * @template-extends TestMultiThreadArrayDataHandleProcess<iterable{posting_number: string, status: string}>
 */
class TestMultiThreadGeneratorDataHandleProcess extends TestMultiThreadArrayDataHandleProcess
{
    protected function data(): iterable
    {
        foreach (json_decode(file_get_contents(__DIR__ . '/jsonList.json'), true) as $item) {
            yield $item;
        };
    }
}
