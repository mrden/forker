<?php

namespace Mrden\Forker\Traits;

use Mrden\Forker\Contracts\Storage;
use Mrden\Forker\Storage\FileStorage;

trait ProcessFileStorageTrait
{
    /**
     * @var FileStorage
     */
    protected $pidStorage;

    protected function pidStorage(): Storage
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FileStorage($this);
        }
        return $this->pidStorage;
    }
}
