<?php

namespace Mrden\Fork\Traits;

use Mrden\Fork\ProcessPidStorageInterface;
use Mrden\Fork\Storage\FileStorage;

trait ProcessFileStorageTrait
{
    /**
     * @var FileStorage
     */
    protected $pidStorage;

    public function pidStorage(): ProcessPidStorageInterface
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FileStorage($this, sys_get_temp_dir());
        }
        return $this->pidStorage;
    }
}