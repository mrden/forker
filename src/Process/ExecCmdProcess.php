<?php

namespace Mrden\Forker\Process;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Traits\ProcessFileStorageTrait;

class ExecCmdProcess extends Process
{
    use ProcessFileStorageTrait;

    /**
     * @inheritDoc
     */
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
        if (!$command) {
            return;
        }
        if (\strpos($command, '> /dev/null 2>&1 &') === false) {
            $command = $command . ' > /dev/null 2>&1 &';
        }
        \exec($command);
    }
}
