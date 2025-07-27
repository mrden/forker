<?php

namespace Mrden\Forker\Contracts;

interface PidAware
{
    public function notifyPid(int $cloneNumber, int $pid): void;
}
