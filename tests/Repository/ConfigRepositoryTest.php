<?php

namespace Portknock\Tests\Repository;

use Portknock\Helper\FileHandler;
use Portknock\Model\Config;
use Portknock\Repository\ConfigRepository;
use Portknock\Tests\AbstractCase;

class ConfigRepositoryTest extends AbstractCase
{
    public function testGetConfig(): void
    {
        $expectedConfig = new Config(
            self::TEST_REDIRECT_V4,
            self::TEST_REDIRECT_V6,
        );

        $configFile = json_encode([
            Config::FIELD_V4_REDIRECT_HOST => self::TEST_REDIRECT_V4,
            Config::FIELD_V6_REDIRECT_HOST => self::TEST_REDIRECT_V6,
        ]);

        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(ConfigRepository::FILE_CONFIG)
            ->willReturn(true);
        $mockFileHandler->expects($this->once())
            ->method('fileGetContents')
            ->with(ConfigRepository::FILE_CONFIG)
            ->willReturn($configFile);

        $configRepository = new ConfigRepository($mockFileHandler);
        self::assertEquals($expectedConfig, $configRepository->getConfig());
        // Second time should hit the "cache" variable
        self::assertEquals($expectedConfig, $configRepository->getConfig());
    }

    public function testNoUserFileExist(): void
    {
        $mockFileHandler = $this->createMock(FileHandler::class);
        $mockFileHandler->expects($this->once())
            ->method('fileExists')
            ->with(ConfigRepository::FILE_CONFIG)
            ->willReturn(false);
        $mockFileHandler->expects($this->once())
            ->method('filePutContents')
            ->with(ConfigRepository::FILE_CONFIG, json_encode([]));

        $configRepository = new ConfigRepository($mockFileHandler);
        $actualConfig     = $configRepository->getConfig();
        self::assertEquals(new Config(null, null), $actualConfig);
    }
}
