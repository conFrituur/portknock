<?php

namespace Portknock\Tests;

use Portknock\Knock;
use Portknock\Util\KnockUtils;

class KnockTest extends AbstractTest
{
    public function testSuccessfulKnock()
    {
        $headers = $this->getTestHeaders();
        $mockUtils = $this->createMock(KnockUtils::class);

        $expectedWhitelist = [
            self::TEST_USER => self::REMOTE_ADDR,
        ];

        // getRemoteAddressFromHeaders
        $mockUtils->expects($this->once())
            ->method('isValidIPv4')
            ->with(self::REMOTE_ADDR)
            ->willReturn(false);

        $mockUtils->expects($this->once())
            ->method('isValidIPv6')
            ->with(self::REMOTE_ADDR)
            ->willReturn(true);

        // getAuthorizedUserFromHeaders - no mocks required
        // addIpToWhitelist

        $mockUtils->expects($this->once())
            ->method('getOrCreateFile')
            ->with(KnockUtils::FILE_WHITELIST)
            ->willReturn(json_encode([])); // empty whitelist

        $mockUtils->expects($this->once())
            ->method('save')
            ->with(KnockUtils::FILE_WHITELIST, json_encode($expectedWhitelist));

        $mockUtils->expects($this->once())
            ->method('addLogEntry')
            ->with(self::REMOTE_ADDR . " has been added to the whitelist for " . self::TEST_USER);


        $knock = new Knock($mockUtils);
        $knock->knock($headers);

    }

}
