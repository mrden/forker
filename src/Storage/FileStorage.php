<?php

namespace Mrden\Fork\Storage;

use Mrden\Fork\ProcessInterface;

class FileStorage extends AbstractStorageProcess
{
    /**
     * @var string
     */
    private $dirname;

    public function __construct(ProcessInterface $process, string $dirname)
    {
        if (file_exists($dirname)) {
            $this->dirname = $dirname;
        }
        parent::__construct($process);
    }

    public function get(int $number): int
    {
        return (int)@\file_get_contents($this->fileName($number));
    }

    public function remove(int $number): void
    {
        $file = $this->fileName($number);
        if (\file_exists($file)) {
            \unlink($file);
        }
    }

    public function save(int $pid, int $number): void
    {
        $fileName = $this->fileName($number);
        \file_put_contents($fileName, $pid);
    }

    private function fileName(int $number): string
    {
        $dir = \rtrim($this->dirname, '/') . '/' . 'forker' . '/' .  $this->slugify($this->processUid) . '/';
        if (!\file_exists($dir)) {
            fwrite(\STDOUT, 'dir - ' . $dir . PHP_EOL);
            \mkdir($dir, 0775, true);
        }
        return $dir . $number . '.pid';
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