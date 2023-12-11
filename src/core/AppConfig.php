<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use Laminas\Config\Config;
use Laminas\Config\Factory as LaminasConfigFactory;

if (!defined('ABS_PATH')) {
    return;
}
class AppConfig
{
    private static Config $config;
    private string $config_file;
    
    private function __construct(string $config_file) 
    {
        $this->config_file = $config_file;
        $this->config = LaminasConfigFactory::fromFile($config_file, true);
    }

    public static function loadAppConfig(string $app): ?AppConfig
    {
        $config_file = CONFIG_DIR . DIRECTORY_SEPARATOR . $app. '.xml';

        if (!file_exists($config_file)) {
            return null;
        }

        return new self($config_file);
    }

    /**
     * Save the configuration to the file.
     */
    public function save(): bool
    {
        return LaminasConfigFactory::toFile($this->config_file, $this->config);
    }
}
