<?php

namespace Mrden\Forker\Storage;

use Mrden\Forker\Contracts\Storage;
use Mrden\Forker\Contracts\Unique;

final class FileStorage extends Storage
{
    /**
     * @var string
     */
    private $dirname;

    public function __construct(Unique $unique, ?string $dirname = null)
    {
        if ($dirname && !\file_exists($dirname)) {
            \mkdir($dirname, 0755, true);
        }
        $dirname = $dirname ?? \sys_get_temp_dir();
        $this->dirname = $dirname;
        parent::__construct($unique);
    }

    public function get(int $key): int
    {
        $file = $this->fileName($key);
        return (int)@\file_get_contents($file);
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
        \file_put_contents($fileName, $value);
    }

    private function fileName(int $key): string
    {
        $dir = \rtrim($this->dirname, '/') . '/' . 'forker' . '/' .  $this->slugify($this->uid) . '/';
        if (!\file_exists($dir)) {
            \mkdir($dir, 0775, true);
        }
        return $dir . $key . '.storage';
    }

    private function slugify(string $string): string
    {
        \Transliterator::create('Russian-Latin/BGN')->transliterate($string);
        $string = \preg_replace('/[^a-zA-Z0-9=\s—–\-]+/u', '', $string);
        $string = \preg_replace('/[=\s—–\-]+/u', '-', $string);
        $string = \trim($string, '-');
        return \strtolower($string);
    }
}
