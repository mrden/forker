<?php

namespace Mrden\Fork\Contracts\Interfaces;

use Mrden\Fork\Contracts\Process;

interface Parental
{
    public function setIsChildContext(bool $isChildContext): void;

    /**
     * @psalm-return array{array{process:class-string<Process>, params?:array, count?: int}}
     */
    public function children(): array;
}
