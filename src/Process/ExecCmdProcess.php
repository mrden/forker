<?php

namespace Mrden\Forker\Process;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Traits\FilePidStorageTrait;

class ExecCmdProcess extends Process
{
    use FilePidStorageTrait;

    private string $command;

    protected function configure(array $params): void
    {
        if (!isset($params['cmd']) || !\is_string($params['cmd']) || empty(\trim($params['cmd']))) {
            throw new \InvalidArgumentException('Parameter "cmd" is required and must be a non-empty string');
        }

        $this->command = \trim($params['cmd']);
    }

    protected function prepare(): void
    {
        if (!\str_contains($this->command, '> /dev/null 2>&1 &')) {
            $this->command .= ' > /dev/null 2>&1 &';
        }
    }

    protected function cleanup(): void
    {
    }

    protected function execute(): void
    {
        \exec($this->command);
    }
}
