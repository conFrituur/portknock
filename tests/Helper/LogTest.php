<?php

namespace Portknock\Tests\Helper;

use Monolog\Handler\NoopHandler;
use Monolog\Logger;
use Portknock\Helper\Log;
use Portknock\Tests\AbstractCase;

class LogTest extends AbstractCase
{
    public function testLogFunctions(): void
    {
        $message = "TestMessage";

        Log::addLogger(new Logger(__CLASS__, [new NoopHandler()]));

        Log::debug($message);
        Log::info($message);
        Log::notice($message);
        Log::warning($message);
        Log::error($message);
        Log::critical($message);

        static::assertTrue($this->logHandler->hasDebugThatContains($message));
        static::assertTrue($this->logHandler->hasInfoThatContains($message));
        static::assertTrue($this->logHandler->hasNoticeThatContains($message));
        static::assertTrue($this->logHandler->hasWarningThatContains($message));
        static::assertTrue($this->logHandler->hasErrorThatContains($message));
        static::assertTrue($this->logHandler->hasCriticalThatContains($message));
    }
}
