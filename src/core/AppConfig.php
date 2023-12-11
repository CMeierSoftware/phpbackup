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
    private const TMP_DIR = 'temp_';
    private static array $config;
    private readonly string $configFile;
    private readonly string $tempDir;

    private function __construct(string $configFile, string $tempDir)
    {
        $this->configFile = $configFile;
        $this->config = LaminasConfigFactory::fromFile($configFile);
        $this->tempDir = $tempDir;
    }

    public static function loadAppConfig(string $app): ?AppConfig
    {
        $config_file = CONFIG_DIR . $app . '.xml';
        $tempDir = CONFIG_DIR . self::TMP_DIR  . $app;

        if (!file_exists($config_file)) {
            return null;
        }

        return new self($config_file, $tempDir);
    }

    public function getDatabase(): ?array
    {
        return isset($this->config['backup']['database']) ? $this->config['backup']['database'] : null;
    }
    public function getDirectory() :?array
    {
        return isset($this->config['backup']['directory']) ? $this->config['backup']['directory'] : null;
    }

    public function getTmp(): string
    {
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0644, true);
        }
        return $this->tempDir.DIRECTORY_SEPARATOR;
    }
}
