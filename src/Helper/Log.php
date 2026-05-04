<?php

namespace Portknock\Helper;

use Psr\Log\LoggerInterface;

class Log
{
    /**
     * @var LoggerInterface[]
     */
    private static array $loggers = [];

    private static array $persistentContext = [];

    public static function setLogger(LoggerInterface $logger): void
    {
        self::$loggers = [$logger];
    }

    public static function addLogger(LoggerInterface $logger): void
    {
        self::$loggers[] = $logger;
    }

    /**
     * @return LoggerInterface[]
     */
    public static function getLoggers(): array
    {
        return self::$loggers;
    }

    public static function setPersistentContext(array $persistentContext): void
    {
        self::$persistentContext = $persistentContext;
    }

    public static function addPersistentContext(array $requestContext): void
    {
        self::$persistentContext = array_merge(self::$persistentContext, $requestContext);
    }

    public static function finalizeContext(array $context): array
    {
        return array_merge(self::$persistentContext, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->debug($message, self::finalizeContext($context));
        }
    }

    public static function info(string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->info($message, self::finalizeContext($context));
        }
    }

    public static function notice(string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->notice($message, self::finalizeContext($context));
        }
    }

    public static function warning(string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->warning($message, self::finalizeContext($context));
        }
    }

    public static function error(string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->error($message, self::finalizeContext($context));
        }
    }

    public static function critical(string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->critical($message, self::finalizeContext($context));
        }
    }
}
