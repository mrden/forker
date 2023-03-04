<?php

namespace Mrden\Fork\Traits;

use Mrden\Fork\Contracts\ProcessPidStorage;
use Mrden\Fork\Storage\FilePidStorage;

trait ProcessFileStorageTrait
{
    /**
     * @var FilePidStorage
     */
    protected $pidStorage;

    protected function pidStorage(): ProcessPidStorage
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FilePidStorage($this, sys_get_temp_dir());
        }
        return $this->pidStorage;
    }
}
