<?php

namespace Portknock\Tests\Repository;

use Portknock\Helper\FileHandler;
use Portknock\Repository\KeyRepository;
use Portknock\Tests\AbstractCase;

class KeyRepositoryTest extends AbstractCase
{
    public function testGetKey(): void
    {
        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(KeyRepository::FILE_KEY)
            ->willReturn(true);
        $mockFileHandler->expects($this->once())
            ->method('fileGetContents')
            ->with(KeyRepository::FILE_KEY)
            ->willReturn(self::TEST_KEY);

        $keyRepository = new KeyRepository($mockFileHandler);
        self::assertEquals(self::TEST_KEY, $keyRepository->getKey());
    }

    public function testGetKeyGeneration(): void
    {
        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(KeyRepository::FILE_KEY)
            ->willReturn(true);
        $mockFileHandler->expects($this->once())
            ->method('fileGetContents')
            ->with(KeyRepository::FILE_KEY)
            ->willReturn('');
        $mockFileHandler->expects($this->once())
            ->method('filePutContents')
            ->with(KeyRepository::FILE_KEY, $this->callback(function ($arg) {
                return is_string($arg) && strlen($arg) === 64;
            }));

        $keyRepository = new KeyRepository($mockFileHandler);
        self::assertSame(64, strlen($keyRepository->getKey()));
    }
}
