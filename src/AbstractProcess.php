<?php

namespace Mrden\Fork;

use Mrden\Fork\Exceptions\ParamException;

abstract class AbstractProcess implements ProcessInterface
{
    protected $maxCloneProcessCount = 5;
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

    public function getMaxCloneProcessCount(): int
    {
        return $this->maxCloneProcessCount;
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

    public function prepare(int $number): void
    {
        if ($this->getParentProcess()) {
            $this->getParentProcess()->isParent(true);
            $title = '    ' . $this->title();
        } else {
            $title = $this->title();
        }
        cli_set_process_title(sprintf(
            '%s (%d)',
            $title,
            $number
        ));
    }

    private function title(?ProcessInterface $process = null): string
    {
        $process = $process ?? $this;
        if ($process->isParent()) {
            $title = 'parent pid ' . \posix_getppid();
        } else {
            $title = \get_class($process) .
                ($process->getParams() ? ' ' . $this->paramToString($process->getParams()) : '');
        }
        if ($process->getParentProcess()) {
            $title = $this->title($process->getParentProcess()) . ' > ' . $title;
        }
        return $title;
    }

    private function paramToString(array $params): string
    {
        foreach ($params as &$param) {
            if (\mb_strwidth($param) > 25) {
                $param = \mb_strimwidth($param, 0, 10, '...') . \mb_substr($param, -15);
            }
        }

        return '[' . trim(str_replace(
                ['array (', ')'],
                '',
                var_export($params, true)
            ), " \t\n\r,") . ']';
    }

    /**
     * @throws ParamException
     */
    abstract protected function checkParams(): void;
}