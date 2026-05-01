<?php

namespace Portknock\Tests\Repository;

use Portknock\Helper\FileHandler;
use Portknock\Model\Allowlist;
use Portknock\Repository\AllowlistRepository;
use Portknock\Tests\AbstractCase;

class AllowlistRepositoryTest extends AbstractCase
{
    public function testGetList(): void
    {
        $expectedAllowlist = $this->getTestAllowlist();

        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(AllowlistRepository::FILE_ALLOWLIST)
            ->willReturn(true);
        $mockFileHandler->expects($this->once())
            ->method('fileGetContents')
            ->with(AllowlistRepository::FILE_ALLOWLIST)
            ->willReturn($this->getTestAllowlistJson());

        $allowlistRepository = new AllowlistRepository($mockFileHandler);
        $actualAllowlist = $allowlistRepository->getList();

        self::assertEquals($expectedAllowlist, $actualAllowlist);
    }

    public function testGetListJsonDecodeError(): void
    {
        $expectedAllowlist = new Allowlist([]);

        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(AllowlistRepository::FILE_ALLOWLIST)
            ->willReturn(true);
        $mockFileHandler->expects($this->once())
            ->method('fileGetContents')
            ->with(AllowlistRepository::FILE_ALLOWLIST)
            ->willReturn('[23]34[f');

        $allowlistRepository = new AllowlistRepository($mockFileHandler);
        $actualAllowlist = $allowlistRepository->getList();

        self::assertEquals($expectedAllowlist, $actualAllowlist);
    }

    public function testSave(): void
    {
        $expectedJson = $this->getTestAllowlistJson();

        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('filePutContents')
            ->with(AllowlistRepository::FILE_ALLOWLIST, $expectedJson);

        $allowlistRepository = new AllowlistRepository($mockFileHandler);
        $allowlistRepository->save($this->getTestAllowlist());
    }
}
