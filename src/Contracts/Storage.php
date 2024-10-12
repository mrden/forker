<?php

namespace Mrden\Forker\Contracts;

use Ramsey\Uuid\Uuid;

abstract class Storage
{
    /**
     * @var string
     */
    protected $uid;

    public function __construct(Unique $unique)
    {
        $this->uid = Uuid::uuid5(
            Uuid::NAMESPACE_X500,
            $unique->uuid()
        )->toString();
    }

    /**
     * @psalm-param positive-int $key
     * @psalm-return positive-int
     */
    abstract public function get(int $key): int;
    /**
     * @psalm-param positive-int $key
     */
    abstract public function remove(int $key): void;
    /**
     * @psalm-param positive-int $value
     * @psalm-param positive-int $key
     */
    abstract public function save(int $key, int $value): void;
}
