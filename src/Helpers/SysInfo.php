<?php

namespace Mrden\Forker\Helpers;

class SysInfo
{
    public static function numCpu(): ?int
    {
        if (\defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $str = \trim(\shell_exec('wmic cpu get NumberOfCores 2>&1'));
            if (!\preg_match('/(\d+)/', $str, $matches)) {
                throw new \RuntimeException('wmic failed to get number of cpu cores on windows!');
            }
            return (int)$matches[1];
        }
        $ret = @\shell_exec('nproc');
        if (\is_string($ret)) {
            $ret = \trim($ret);
            if (false !== ($tmp = \filter_var($ret, FILTER_VALIDATE_INT))) {
                return (int)$tmp;
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
