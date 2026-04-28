<?php

namespace Portknock\Util;

class KnockUtils
{
    const string FILE_HISTORY = "../../data/history.log";
    const string FILE_WHITELIST = "../../data/whitelist.json";

    public function getOrCreateFile(string $filename): string
    {
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            if ($content === false) {
                $this->die(500);
            }
            return strval($content);
        }
        file_put_contents($filename, '');
        $this->addLogEntry("created file {$filename}");
        return '';
    }

    public function save(string $filename, string $contents): void
    {
        file_put_contents("{$filename}", $contents);
    }

    public function isValidIPv4(string $ip): bool
    {
        $opts = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE;
        return filter_var($ip, FILTER_VALIDATE_IP, $opts) !== false;
    }

    public function isValidIPv6(string $ip): bool
    {
        $opts = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE;
        return filter_var($ip, FILTER_VALIDATE_IP, $opts) !== false;
    }

    public function addLogEntry(string $logEntry): void
    {
        $history = $this->getOrCreateFile(self::FILE_HISTORY);
        $historyLines = explode(PHP_EOL, $history);

        $date = date('Y-m-d H:i:s');
        $newEntry = "{$date} - {$logEntry}";

        // New entry on top
        array_unshift($historyLines, $newEntry);

        // Make sure the logfile doesn't get too big
        $truncatedHistoryLines = array_slice($historyLines, 0, 500);

        file_put_contents(self::FILE_HISTORY, implode(PHP_EOL, $truncatedHistoryLines));
    }

    public function die(int $code): void
    {
        http_response_code($code);
        die("{$code} NEIN!");
    }
}
