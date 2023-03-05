<?php

namespace Tests\src;

use Tests\src\Traits\ProcessFileStorageTrait;

class TestSingleProcess extends \Mrden\Fork\Contracts\Process
{
    use ProcessFileStorageTrait;

    protected function checkParams(): void
    {
    }

    public function execute(): void
    {
        $params = $this->getParams();
        sleep($params['time'] ?? 11);
    }

    protected function prepare(): void
    {
    }
}
