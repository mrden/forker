<?php

namespace Tests\Traits;

use Mrden\Fork\Contracts\ProcessPidStorage;
use Mrden\Fork\Storage\FilePidStorage;

trait ProcessFileStorageTrait
{
    /**
     * @var FilePidStorage
     */
    protected $pidStorage;

    public function pidStorage(): ProcessPidStorage
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FilePidStorage($this, __DIR__ . '/../storage');
        }
        return $this->pidStorage;
    }
}