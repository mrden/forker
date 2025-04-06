<?php

namespace Mrden\Forker\Helpers;

class SysInfo
{
    /**
     * @psalm-return int<1, max>|null
     */
    public static function numCpu(): ?int
    {
        if (\defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $ret = @\shell_exec('wmic cpu get NumberOfCores 2>&1');
            if (\is_string($ret)) {
                if (!\preg_match('/(\d+)/', \trim($ret), $matches)) {
                    throw new \RuntimeException('wmic failed to get number of cpu cores on windows!');
                }
                $count = (int) $matches[1];
                if ($count > 0) {
                    return $count;
                }
            }
            return null;
        }
        $ret = @\shell_exec('nproc');
        if (\is_string($ret)) {
            if (false !== ($count = \filter_var(\trim($ret), FILTER_VALIDATE_INT))) {
                if ($count > 0) {
                    return $count;
                }
                return null;
            }
        }
        if (\is_readable('/proc/cpuinfo')) {
            $cpuInfo = \file_get_contents('/proc/cpuinfo');
            $count = \substr_count($cpuInfo, 'processor');
            if ($count > 0) {
                return $count;
            }
        }
        return null;
    }

    public static function isCli(): bool
    {
        return \php_sapi_name() === 'cli';
    }
}
