<?php

namespace Mrden\Fork\Process;

use Mrden\Fork\Contracts\Interfaces\Parental;
use Mrden\Fork\Contracts\Process;
use Mrden\Fork\Traits\ProcessFileStorageTrait;

final class CallableProcess extends Process
{
    use ProcessFileStorageTrait;

    /**
     * @psalm-var callable(CallableProcess): void
     */
    private $logic;

    public function __construct(callable $logic, array $params = [], ?Parental $parentProcess = null)
    {
        $this->logic = $logic;
        parent::__construct($params, $parentProcess);
    }

    public function uuid(): string
    {
        return \spl_object_hash((object)$this->logic) . parent::uuid();
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
