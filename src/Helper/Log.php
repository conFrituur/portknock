<?php

namespace Portknock\Helper;

use Psr\Log\LoggerInterface;

class Log
{
    /**
     * @var LoggerInterface[]
     */
    private static array $loggers = [];

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

    private static function formatMessage(string $tag, string $message): string
    {
        $tag = substr($tag, 0, 50);
        return "[{$tag}] {$message}";
    }

    public static function debug(string $tag, string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->debug(self::formatMessage($tag, $message), $context);
        }
    }

    public static function info(string $tag, string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->info(self::formatMessage($tag, $message), $context);
        }
    }

    public static function notice(string $tag, string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->notice(self::formatMessage($tag, $message), $context);
        }
    }

    public static function warning(string $tag, string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->warning(self::formatMessage($tag, $message), $context);
        }
    }

    public static function error(string $tag, string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->error(self::formatMessage($tag, $message), $context);
        }
    }

    public static function critical(string $tag, string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->critical(self::formatMessage($tag, $message), $context);
        }
    }

    public static function alert(string $tag, string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->alert(self::formatMessage($tag, $message), $context);
        }
    }

    public static function emergency(string $tag, string $message, array $context = []): void
    {
        foreach (self::getLoggers() as $logger) {
            $logger->emergency(self::formatMessage($tag, $message), $context);
        }
    }
}
