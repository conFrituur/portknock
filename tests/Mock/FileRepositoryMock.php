<?php

namespace Portknock\Tests\Mock;

use Portknock\Repository\AbstractFileRepository;

class FileRepositoryMock extends AbstractFileRepository
{
    public function callGetOrCreateFile(string $filename, string $defaultContent = ''): string
    {
        return $this->getOrCreateFile($filename, $defaultContent);
    }

    public function callLoadFile(string $filename): string|false
    {
        return $this->loadFile($filename);
    }

    public function callSaveFile(string $filename, string $content): void
    {
        $this->saveFile($filename, $content);
    }
}
