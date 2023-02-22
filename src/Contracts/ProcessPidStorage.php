<?php

namespace Mrden\Fork\Contracts;

use Ramsey\Uuid\Uuid;

abstract class ProcessPidStorage
{
    /**
     * @var string
     */
    protected $processUid;

    public function __construct(Process $process)
    {
        $this->processUid = Uuid::uuid5(
            Uuid::NAMESPACE_X500,
            $process->uuid()
        )->toString();
    }

    abstract public function get(int $cloneNumber): int;
    abstract function remove(int $cloneNumber): void;
    abstract function save(int $pid, int $cloneNumber): void;
}