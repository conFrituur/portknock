<?php

namespace Portknock\Repository;

use Portknock\Helper\Log;
use Portknock\Model\Config;

class ConfigRepository extends AbstractFileRepository
{
    public const string FILE_CONFIG = '../data/config.json';

    private ?Config $config = null;

    public function getConfig(): Config
    {
        if (!$this->config) {
            $configJson   = $this->getOrCreateFile(self::FILE_CONFIG, '[]');
            $jsonData     = json_decode($configJson, true, flags: JSON_THROW_ON_ERROR);
            $this->config = Config::fromJsonData($jsonData);
            Log::debug("loaded " . (count($jsonData)) . " config values", ['file' => self::FILE_CONFIG]);
        }
        return $this->config;
    }
}
