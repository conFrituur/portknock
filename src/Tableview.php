<?php

namespace Portknock;

use Portknock\Util\KnockUtils;

class TableView
{
    private KnockUtils $utils;

    public function getUtils(): KnockUtils
    {
        return $this->utils;
    }

    public function __construct(array $headers)
    {
        $this->utils = new KnockUtils();

        $this->checkAuthorizedUserFromHeaders($headers);
        $whitelist = $this->getWhitelist();
        echo $this->buildOPNsenseTable($whitelist);
    }

    private function checkAuthorizedUserFromHeaders(array $headers): void
    {
        $authorized = [
            'test',
        ];

        $sesamHeader = $headers['HTTP_X_SESAM'] ?? 'UNSET';

        if (!in_array($sesamHeader, $authorized)) {
            $this->getUtils()->die(401);
        }
    }

    private function getWhitelist(): array
    {
        $whitelistFile = file_get_contents('whitelist.json');
        $whitelist = json_decode($whitelistFile, true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($whitelist)) {
            $this->getUtils()->die(500);
        }

        // Remove any duplicate IP's from whitelist
        return array_values(array_unique($whitelist));
    }

    /**
     * OPNsense table format:
     * "The content of the file being fetched should contain one IPv[4|6] address per line,
     * lines that start with a whitespace , colon (,), semicolon (;), pipe (|) or hash (#) will be ignored."
     *
     * @param array $whitelist
     * @return string
     */
    private function buildOPNsenseTable(array $whitelist): string
    {
        return implode(PHP_EOL, $whitelist);
    }
}

(new TableView($_SERVER));
