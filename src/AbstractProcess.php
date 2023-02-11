<?php

namespace Mrden\Fork;

use Mrden\Fork\Exceptions\ParamException;

abstract class AbstractProcess implements ProcessInterface
{
    protected $maxChildProcessCount = 5;
    protected $isParent = false;

    protected $params;
    protected $parentProcess;
    protected $cloneNumber;

    /**
     * @throws ParamException
     */
    public function __construct(array $params = [], ?ProcessInterface $parentProcess = null)
    {
        $this->params = $params;
        $this->parentProcess = $parentProcess;
        $this->cloneNumber = 1;
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
    public function cloneNumber(?int $number = null): int
    {
        if ($number === null) {
            return $this->cloneNumber;
        }
        $this->cloneNumber = $number;
        return $number;
    }

    /**
     * @throws ParamException
     */
    protected function paramException(string $message): void
    {
        if ($this->getParentProcess()) {
            throw new ParamException(static::class . ': ' . $message);
        }
        throw new ParamException($message);
    }

    /**
     * @throws ParamException
     */
    abstract protected function checkParams(): void;
}