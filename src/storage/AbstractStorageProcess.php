<?php

namespace Mrden\Fork\storage;

use Mrden\Fork\ProcessInterface;
use Mrden\Fork\ProcessUuidInterface;
use Mrden\Fork\ProcessPidStorageInterface;
use Ramsey\Uuid\Uuid;

abstract class AbstractStorageProcess implements ProcessPidStorageInterface
{
    /**
     * @var string
     */
    protected $processUid;

    public function __construct(ProcessInterface $process)
    {
        if ($process instanceof ProcessUuidInterface) {
            $this->processUid = $process->uuid();
        } else {
            $this->processUid = Uuid::uuid5(
                Uuid::NAMESPACE_X500,
                \get_class($process) . \serialize($process->getParams())
            );
        }
    }
}