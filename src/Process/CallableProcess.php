<?php

namespace Mrden\Forker\Process;

use Mrden\Forker\Contracts\Process;
use Mrden\Forker\Traits\ProcessFileStorageTrait;

final class CallableProcess extends Process
{
    use ProcessFileStorageTrait;

    /**
     * @psalm-var callable(CallableProcess): void
     */
    private $logic;

    public function __construct(callable $logic, array $params = [])
    {
        $this->logic = $logic;
        parent::__construct($params);
    }

    public function uuid(): string
    {
        return \spl_object_hash((object) $this->logic) . parent::uuid();
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
