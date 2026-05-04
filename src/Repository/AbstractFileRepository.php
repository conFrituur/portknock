<?php

namespace Portknock\Repository;

use Portknock\Helper\Log;
use Portknock\Helper\FileHandler;
use RuntimeException;

abstract class AbstractFileRepository
{
    private FileHandler $fileHandler;

    public function __construct(?FileHandler $fileHandler = null)
    {
        $this->fileHandler = $fileHandler ?? new FileHandler();
    }

    protected function getOrCreateFile(string $filename): string
    {
        $contents = $this->loadFile($filename);
        if ($contents === false) {
            $this->fileHandler->filePutContents($filename, '');
            Log::notice("Created file $filename");
            $contents = '';
        }
        return $contents;
    }

    protected function loadFile(string $filename): string|false
    {
        if ($this->fileHandler->fileExists($filename)) {
            $contents = $this->fileHandler->fileGetContents($filename);

            if ($contents === false) {
                throw new RuntimeException("Error reading file {$filename}");
            }
            return $contents;
        }
        return false;
    }

    protected function saveFile(string $filename, string $contents): void
    {
        $this->fileHandler->filePutContents($filename, $contents);
    }
}
