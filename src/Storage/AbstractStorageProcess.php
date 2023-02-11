<?php

namespace Mrden\Fork\Storage;

use Mrden\Fork\ProcessInterface;
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
        $this->processUid = Uuid::uuid5(
            Uuid::NAMESPACE_X500,
            $this->uuidName($process)
        );
    }

    private function uuidName(ProcessInterface $process): string
    {
        $name = \get_class($process) . \serialize($process->getParams());
        if ($process->getParentProcess()) {
            $name = $this->uuidName($process->getParentProcess()) . $process->getParentProcess()->cloneNumber() . $name;
        }
        return $name;
    }
}