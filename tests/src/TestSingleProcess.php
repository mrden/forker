<?php

namespace Tests\src;

use Tests\src\Traits\ProcessFileStorageTrait;

class TestSingleProcess extends \Mrden\Fork\Contracts\Process
{
    use ProcessFileStorageTrait;

    protected $maxCloneCount = 6;

    protected function checkParams(): void
    {
    }

    public function execute(): void
    {
        $params = $this->getParams();
        $i = 0;
        while ($i < 180000000) {
            $i += 0.5;
        }
        //sleep($params['time'] ?? 11);
    }

    protected function prepare(): void
    {
    }
}
