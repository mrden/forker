<?php

namespace Mrden\Forker\Exceptions;

class ProcessTimeoutException extends \RuntimeException
{
    public function __construct(int $pid, int $timeout, ?\Throwable $previous = null)
    {
        $message = \sprintf('Process with PID %d did not stop within %d seconds timeout', $pid, $timeout);
        parent::__construct($message, 0, $previous);
    }
}
