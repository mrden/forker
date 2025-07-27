<?php

namespace Mrden\Forker\Traits;

use Mrden\Forker\Contracts\PidStorage;
use Mrden\Forker\Storage\FilePidStorage;

trait FilePidStorageTrait
{
    protected FilePidStorage|null $pidStorage = null;

    protected function getPidStorage(): PidStorage
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FilePidStorage($this);
        }
        return $this->pidStorage;
    }
}
