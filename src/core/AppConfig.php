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
    private static array $config;
    private string $config_file;

    private function __construct(string $config_file)
    {
        $this->config_file = $config_file;
        $this->config = LaminasConfigFactory::fromFile($config_file);
    }

    public static function loadAppConfig(string $app): ?AppConfig
    {
        $config_file = CONFIG_DIR . DIRECTORY_SEPARATOR . $app . '.xml';

        if (!file_exists($config_file)) {
            return null;
        }

        return new self($config_file);
    }

    public function getDatabase()
    {
        return isset($this->config['backup']['database']) ? $this->config['backup']['database'] : null;
    }
    public function getDirectory()
    {
        return isset($this->config['backup']['directory']) ? $this->config['backup']['directory'] : null;
    }
}
