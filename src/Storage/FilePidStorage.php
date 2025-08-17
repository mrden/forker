<?php

namespace Mrden\Forker\Storage;

use Mrden\Forker\Contracts\PidStorage;
use Mrden\Forker\Contracts\Unique;

final class FilePidStorage implements PidStorage
{
    private string $uniqId;
    private string $dirname;

    public function __construct(Unique $unique, ?string $dirname = null)
    {
        if ($dirname !== null && !\file_exists($dirname)) {
            \mkdir($dirname, 0755, true);
        }
        $dirname = $dirname ?? \sys_get_temp_dir();
        $this->dirname = $dirname;
        $this->uniqId = $unique->id();
    }

    public function get(int $index): ?int
    {
        $file = $this->fileName($index);
        $pidFromFile = (int) @\file_get_contents($file);
        if ($pidFromFile > 0) {
            return $pidFromFile;
        }
        return null;
    }

    public function remove(int $index): void
    {
        $file = $this->fileName($index);
        if (\file_exists($file)) {
            \unlink($file);
        }
    }

    public function save(int $index, int $pid): void
    {
        $fileName = $this->fileName($index);
        \file_put_contents($fileName, (string) $pid);
    }

    private function fileName(int $key): string
    {
        $dir = \rtrim($this->dirname, '/') . '/' . 'forker' . '/' .  $this->slugify($this->uniqId) . '/';
        if (!\file_exists($dir)) {
            \mkdir($dir, 0775, true);
        }
        return $dir . $key . '.storage';
    }

    private function slugify(string $string): string
    {
        $transliterator = \Transliterator::create('Russian-Latin/BGN');
        $string = (string) ($transliterator ? $transliterator->transliterate($string) : $string);
        $string = \preg_replace('/[^a-zA-Z0-9=\s—–\-]+/u', '', $string);
        $string = \preg_replace('/[=\s—–\-]+/u', '-', $string);
        $string = \trim($string, '-');
        return \strtolower($string);
    }
}
