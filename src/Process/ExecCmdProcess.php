<?php

namespace Mrden\Forker\Process;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Traits\FilePidStorageTrait;

class ExecCmdProcess extends Process
{
    use FilePidStorageTrait;

    protected function checkParams(): void
    {
        if (!isset($this->params['cmd'])) {
            throw new \LogicException('Param "cmd" required');
        }
    }

    protected function prepare(): void
    {
    }

    protected function execute(): void
    {
        \sleep(1);
        $command = $this->params['cmd'] ?? null;
        if ($command === null) {
            return;
        }
        if (!\str_contains($command, '> /dev/null 2>&1 &')) {
            $command = $command . ' > /dev/null 2>&1 &';
        }
        \exec($command);
    }
}
