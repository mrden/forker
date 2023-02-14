<?php

namespace Mrden\Fork;

use Ramsey\Uuid\Uuid;

abstract class ProcessPidStorage
{
    /**
     * @var string
     */
    protected $processUid;

    public function __construct(ProcessInterface $process)
    {
        $this->processUid = Uuid::uuid5(
            Uuid::NAMESPACE_X500,
            $process->uuid()
        );
    }

    abstract public function get(int $cloneNumber): int;
    abstract function remove(int $cloneNumber): void;
    abstract function save(int $pid, int $cloneNumber): void;
}