<?php

namespace Mrden\Fork;

use Mrden\Fork\exception\ParamException;

abstract class AbstractProcess implements ProcessInterface
{
    protected $maxChildProcessCount = 5;
    protected $isParent = false;

    protected $params;
    protected $parentProcess;

    /**
     * @throws ParamException
     */
    public function __construct(array $params = [], ?ProcessInterface $parentProcess = null)
    {
        $this->params = $params;
        $this->parentProcess = $parentProcess;
        $this->checkParams();
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getParentProcess(): ?ProcessInterface
    {
        return $this->parentProcess;
    }

    public function getMaxChildProcess(): int
    {
        return $this->maxChildProcessCount;
    }

    public function isParent(?bool $isParent = null): bool
    {
        if ($isParent === null) {
            return $this->isParent;
        }
        $this->isParent = $isParent;
        return $isParent;
    }

    /**
     * @throws ParamException
     */
    abstract protected function checkParams(): void;
}