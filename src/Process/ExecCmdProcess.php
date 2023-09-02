<?php

namespace Mrden\Fork\Process;

use Mrden\Fork\Contracts\Process;
use Mrden\Fork\Traits\ProcessFileStorageTrait;

class ExecCmdProcess extends Process
{
    use ProcessFileStorageTrait;

    /**
     * @inheritDoc
     */
    protected function checkParams(): void
    {
        $params = $this->getParams();
        if (!isset($params['cmd'])) {
            throw new \LogicException('Param "cmd" required');
        }
    }

    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
    }

    /**
     * @inheritDoc
     */
    protected function execute(): void
    {
        sleep(1);
        $command = $this->getParams()['cmd'] ?? null;
        if (!$command) {
            return;
        }
        if (strpos($command, '> /dev/null 2>&1 &') === false) {
            $command = $command . ' > /dev/null 2>&1 &';
        }
        exec($command, $o, $rc);
    }
}
