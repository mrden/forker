<?php

namespace Mrden\Forker\Process;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Exceptions\ForkException;
use Mrden\Forker\Forker;
use Mrden\Forker\Traits\SimpleProcessTrait;

class RestartWithForkerBinaryProcess extends Process
{
    use SimpleProcessTrait;

    private Process $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
        parent::__construct([], $this->process->getProcessManager(), \get_class($this->process->getPidStorage()));
    }

    /**
     * @return void
     * @throws ForkException
     */
    protected function execute(): void
    {
        $forkerBinary = __DIR__ . '/../../bin/forker';
        $command = \sprintf(
            '%s %s --process="%s" --count=%d --clone_number=%d',
            PHP_BINARY,
            $forkerBinary,
            \get_class($this->process),
            $this->process->getRunningCloneNumber(),
            $this->process->getRunningCloneNumber()
        );
        foreach ($this->params as $name => $value) {
            $command .= ' --process-' . $name . '="' . $value .'"';
        }
        $command .= ' --process-pid-storage-class="' . \get_class($this->process->getPidStorage()) . '"';
        $command .= ' --process-process-manager-class="' . \get_class($this->process->getProcessManager()) . '"';
        $cmdProcess = new ExecCmdProcess(['cmd' => $command], $this->getProcessManager(), \get_class($this->getPidStorage()));
        $forker = new Forker($cmdProcess);
        $forker->run();
    }

    protected function initGracefulShutdown(): void
    {
    }
}
