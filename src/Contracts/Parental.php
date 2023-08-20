<?php

namespace Mrden\Fork\Contracts;

interface Parental
{
    public function setIsParent(bool $isParent): void;

    /**
     * @psalm-return array{array{process:class-string<Process>, params?:array, count?: int}}
     */
    public function children(): array;
}
