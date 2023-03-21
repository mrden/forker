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

    /**
     * @psalm-param positive-int $cloneNumber
     * @psalm-return positive-int
     */
    abstract public function get(int $cloneNumber): int;
    /**
     * @psalm-param positive-int $cloneNumber
     */
    abstract public function remove(int $cloneNumber): void;
    /**
     * @psalm-param positive-int $pid
     * @psalm-param positive-int $cloneNumber
     */
    abstract public function save(int $pid, int $cloneNumber): void;
}
