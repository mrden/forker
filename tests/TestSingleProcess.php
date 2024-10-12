<?php

namespace Tests;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Contracts\Storage;
use Mrden\Forker\Storage\FileStorage;

class TestSingleProcess extends Process
{
    /**
     * @var FileStorage
     */
    protected $pidStorage;

    protected $maxCloneCount = 6;

    protected function checkParams(): void
    {
    }

    public function execute(): void
    {
        $params = $this->getParams();
        \sleep($params['time'] ?? 11);
    }

    protected function prepare(): void
    {
    }

    protected function pidStorage(): Storage
    {
        if (!isset($this->pidStorage)) {
            $this->pidStorage = new FileStorage($this, __DIR__ . '/../.mrden');
        }
        return $this->pidStorage;
    }
}
