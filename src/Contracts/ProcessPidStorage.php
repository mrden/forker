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
     * @psalm-param positive-int $key
     * @psalm-return positive-int
     */
    abstract public function get(int $key): int;
    /**
     * @psalm-param positive-int $key
     */
    abstract public function remove(int $key): void;
    /**
     * @psalm-param positive-int $pid
     * @psalm-param positive-int $key
     */
    abstract public function save(int $pid, int $key): void;
}
