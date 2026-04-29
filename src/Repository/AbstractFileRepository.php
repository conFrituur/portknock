<?php

namespace Portknock\Repository;

use Portknock\Helper\Log;
use Portknock\Helper\Util;

abstract class AbstractFileRepository
{
    private Util $utils;

    public function __construct(?Util $utils = null)
    {
        $this->utils = $utils ?? new Util();
    }

    protected function getOrCreateFile(string $filename): string
    {
        $contents = $this->loadFile($filename);
        if ($contents === false) {
            file_put_contents($filename, '');
            Log::notice(__CLASS__, "Created file {$filename}");
            $contents = '';
        }
        return $contents;
    }

    protected function loadFile(string $filename): string|false
    {
        if (file_exists($filename)) {
            $contents = file_get_contents($filename);
            if ($contents === false) {
                $this->utils->die(500);
            }
            return strval($contents);
        }
        return false;
    }

    protected function saveFile(string $filename, string $contents): void
    {
        file_put_contents("{$filename}", $contents);
    }
}
