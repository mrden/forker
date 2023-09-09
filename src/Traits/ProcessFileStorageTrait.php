<?php

namespace Mrden\Fork\Traits;

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
            $this->pidStorage = new FileStorage($this);
        }
        return $this->pidStorage;
    }
}
