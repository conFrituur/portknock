<?php

namespace Portknock\Tests\Model;

use Portknock\Model\Config;
use Portknock\Tests\AbstractCase;

class ConfigTest extends AbstractCase
{
    public function testFromJsonData(): void
    {
        $config = Config::fromJsonData([
            Config::FIELD_V4_REDIRECT_HOST => self::TEST_REDIRECT_V4,
            Config::FIELD_V6_REDIRECT_HOST => self::TEST_REDIRECT_V6,
        ]);
        self::assertSame(self::TEST_REDIRECT_V4, $config->getV4RedirectHost());
        self::assertSame(self::TEST_REDIRECT_V6, $config->getV6RedirectHost());

        $config = Config::fromJsonData([]);
        self::assertNull($config->getV4RedirectHost());
        self::assertNull($config->getV6RedirectHost());
    }
}
