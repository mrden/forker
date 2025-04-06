<?php

namespace Mrden\Forker\Process;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Traits\FilePidStorageTrait;

final class CallableProcess extends Process
{
    use FilePidStorageTrait;

    /**
     * @var \Closure(CallableProcess): void
     */
    private \Closure $logic;

    public function __construct(\Closure $logic, array $params = [])
    {
        $this->logic = $logic;
        parent::__construct($params);
    }

    public function id(): string
    {
        return \md5(\spl_object_hash($this->logic) . parent::id());
    }

    protected function checkParams(): void
    {
    }

    protected function prepare(): void
    {
    }

    protected function execute(): void
    {
        \call_user_func($this->logic, $this);
    }
}
