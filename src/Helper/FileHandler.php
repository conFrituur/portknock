<?php

namespace Portknock\Helper;

class FileHandler
{
    public function fileGetContents(string $filename): string|false
    {
        return file_get_contents($filename);
    }

    public function filePutContents(string $filename, string $content): void
    {
        file_put_contents($filename, $content);
    }

    public function fileExists(string $filename): bool
    {
        return file_exists($filename);
    }
}
