<?php

namespace Portknock\Tests;

use Portknock\Knock;
use Portknock\Helper\Util;

class KnockTest extends AbstractTest
{
    public function testSuccessfulKnock()
    {
        $headers = $this->getTestHeaders();
        $mockUtil = $this->createMock(Util::class);

        $expectedWhitelist = [
            self::TEST_USER => self::REMOTE_ADDR,
        ];

        // getRemoteAddressFromHeaders
        $mockUtil->expects($this->once())
            ->method('isValidIPv4')
            ->with(self::REMOTE_ADDR)
            ->willReturn(false);

        $mockUtil->expects($this->once())
            ->method('isValidIPv6')
            ->with(self::REMOTE_ADDR)
            ->willReturn(true);

        // getAuthorizedUserFromHeaders - no mocks required
        // addIpToWhitelist

        $mockUtil->expects($this->once())
            ->method('getOrCreateFile')
            ->with(Util::FILE_WHITELIST)
            ->willReturn(json_encode([])); // empty whitelist

        $mockUtil->expects($this->once())
            ->method('save')
            ->with(Util::FILE_WHITELIST, json_encode($expectedWhitelist));

        $mockUtil->expects($this->once())
            ->method('addLogEntry')
            ->with(self::REMOTE_ADDR . " has been added to the whitelist for " . self::TEST_USER);


        $knock = new Knock($mockUtil);
        $knock->knock($headers);

    }

}
