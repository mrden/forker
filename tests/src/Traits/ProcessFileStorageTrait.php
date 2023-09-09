<?php

namespace Tests\src\Traits;

use Mrden\Fork\Contracts\Storage;
use Mrden\Fork\Storage\FileStorage;

trait ProcessFileStorageTrait
{
    /**
     * @var FileStorage
     */
    protected $pidStorage;

    protected function pidStorage(): Storage
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FileStorage($this, __DIR__ . '/../../storage');
        }
        return $this->pidStorage;
    }
}
