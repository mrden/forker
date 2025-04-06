<?php

namespace Mrden\Forker\Storage;

use Mrden\Forker\Contracts\PidStorage;
use Mrden\Forker\Contracts\Unique;

final class FilePidStorage extends PidStorage
{
    private string $dirname;

    public function __construct(Unique $unique, ?string $dirname = null)
    {
        if ($dirname !== null && !\file_exists($dirname)) {
            \mkdir($dirname, 0755, true);
        }
        $dirname = $dirname ?? \sys_get_temp_dir();
        $this->dirname = $dirname;
        parent::__construct($unique);
    }

    public function get(int $key): ?int
    {
        $file = $this->fileName($key);
        $pidFromFile = (int) @\file_get_contents($file);
        if ($pidFromFile > 0) {
            return $pidFromFile;
        }
        return null;
    }

    public function remove(int $key): void
    {
        $file = $this->fileName($key);
        if (\file_exists($file)) {
            \unlink($file);
        }
    }

    public function save(int $key, int $value): void
    {
        $fileName = $this->fileName($key);
        \file_put_contents($fileName, (string) $value);
    }

    private function fileName(int $key): string
    {
        $dir = \rtrim($this->dirname, '/') . '/' . 'forker' . '/' .  $this->slugify($this->unique->id()) . '/';
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
