<?php

namespace Portknock\Tests\Model;

use Portknock\Tests\AbstractCase;

class HttpHeadersTest extends AbstractCase
{
    public function testGetRemoteAddr(): void
    {
        $headers = $this->getTestHeaders();
        self::assertEquals(self::REMOTE_ADDR, $headers->getRemoteAddr());
    }

    public function testGetSesamHeader(): void
    {
        $headers = $this->getTestHeaders();
        self::assertEquals(self::TEST_SESAM, $headers->getSesam());
    }
}
