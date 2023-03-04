<?php

namespace Mrden\Fork\Storage;

use Mrden\Fork\Contracts\Process;
use Mrden\Fork\Contracts\ProcessPidStorage;

final class FilePidStorage extends ProcessPidStorage
{
    /**
     * @var string
     */
    private $dirname;

    public function __construct(Process $process, string $dirname)
    {
        if (!file_exists($dirname)) {
            \mkdir($dirname, 0755, true);
        }
        $this->dirname = $dirname;
        parent::__construct($process);
    }

    public function get(int $cloneNumber): int
    {
        return (int)@\file_get_contents($this->fileName($cloneNumber));
    }

    public function remove(int $cloneNumber): void
    {
        $file = $this->fileName($cloneNumber);
        if (\file_exists($file)) {
            \unlink($file);
        }
    }

    public function save(int $pid, int $cloneNumber): void
    {
        $fileName = $this->fileName($cloneNumber);
        \file_put_contents($fileName, $pid);
    }

    private function fileName(int $cloneNumber): string
    {
        $dir = \rtrim($this->dirname, '/') . '/' . 'forker' . '/' .  $this->slugify($this->processUid) . '/';
        if (!\file_exists($dir)) {
            \mkdir($dir, 0775, true);
        }
        return $dir . $cloneNumber . '.pid';
    }

    private function slugify(string $string): string
    {
        \Transliterator::create('Russian-Latin/BGN')->transliterate($string);
        $string = preg_replace('/[^a-zA-Z0-9=\s—–\-]+/u', '', $string);
        $string = preg_replace('/[=\s—–\-]+/u', '-', $string);
        $string = \trim($string, '-');
        return \strtolower($string);
    }
}
