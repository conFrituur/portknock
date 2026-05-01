<?php

namespace Portknock\Tests\Repository;

use Portknock\Helper\FileHandler;
use Portknock\Tests\AbstractCase;
use Portknock\Tests\Mock\FileRepositoryMock;

class AbstractFileRepositoryTest extends AbstractCase
{
    public const string FILE_TEST = '/tmp/test';
    public const string TEST_CONTENTS = 'kontents';

    public function testGetOrCreateFile(): void
    {
        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(self::FILE_TEST)
            ->willReturn(true);
        $mockFileHandler->expects($this->once())
            ->method('fileGetContents')
            ->with(self::FILE_TEST)
            ->willReturn(self::TEST_CONTENTS);

        $fileRepository = new FileRepositoryMock($mockFileHandler);
        $actualContents = $fileRepository->callGetOrCreateFile(self::FILE_TEST);

        self::assertSame(self::TEST_CONTENTS, $actualContents);
    }

    public function testGetOrCreateFileFileDoesNotExist(): void
    {
        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(self::FILE_TEST)
            ->willReturn(false);
        $mockFileHandler->expects($this->once())
            ->method('filePutContents')
            ->with(self::FILE_TEST, '');

        $fileRepository = new FileRepositoryMock($mockFileHandler);
        $actualContents = $fileRepository->callGetOrCreateFile(self::FILE_TEST);

        self::assertEmpty($actualContents);
    }

    public function testLoadFile(): void
    {
        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(self::FILE_TEST)
            ->willReturn(true);
        $mockFileHandler->expects($this->once())
            ->method('fileGetContents')
            ->with(self::FILE_TEST)
            ->willReturn(self::TEST_CONTENTS);

        $fileRepository = new FileRepositoryMock($mockFileHandler);
        $actualContents = $fileRepository->callLoadFile(self::FILE_TEST);

        self::assertSame(self::TEST_CONTENTS, $actualContents);
    }

    public function testLoadFileError(): void
    {
        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(self::FILE_TEST)
            ->willReturn(true);
        $mockFileHandler->expects($this->once())
            ->method('fileGetContents')
            ->with(self::FILE_TEST)
            ->willReturn(false);

        $fileRepository = new FileRepositoryMock($mockFileHandler);
        $this->expectException(\RuntimeException::class);
        $fileRepository->callLoadFile(self::FILE_TEST);
    }

    public function testSave(): void
    {
        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('filePutContents')
            ->with(self::FILE_TEST, self::TEST_CONTENTS);

        $fileRepository = new FileRepositoryMock($mockFileHandler);
        $fileRepository->callSaveFile(self::FILE_TEST, self::TEST_CONTENTS);
    }
}
