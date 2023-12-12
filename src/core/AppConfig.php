<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use Laminas\Config\Writer\Xml as XmlWriter;
use Laminas\Config\Reader\Xml as XmlReader;
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
        $tempDir = CONFIG_DIR . self::TMP_DIR . $app;

        if (!file_exists($config_file)) {
            return null;
        }

        return new self($config_file, $tempDir);
    }

    public function getBackupDatabase(): ?array
    {
        return isset($this->config['backup']['database']) ? $this->config['backup']['database'] : null;
    }
    public function getBackupDirectory(): ?array
    {
        return isset($this->config['backup']['directory']) ? $this->config['backup']['directory'] : null;
    }
    public function getBackupSettings(): ?array
    {
        return isset($this->config['backup']['settings']) ? $this->config['backup']['settings'] : null;
    }
    public function getRemoteSettings(): ?array
    {
        return isset($this->config['remote']) ? $this->config['remote'] : null;
    }

    public function getTempDir(): string
    {
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0644, true);
        }
        return $this->tempDir . DIRECTORY_SEPARATOR;
    }

    /**
     * Write data to a file in JSON format.
     *
     * @param string $type The name of the file without extension.
     * @param mixed $data The data to be written to the file.
     *
     * @return bool True on success, false on failure.
     */
    public function saveTempData(string $type, array $data): void
    {
        $filePath = $this->getTempDir() . $type . '.xml';
        $config = new Config($data, false);
        $writer = new XmlWriter();
        $writer->toFile($filePath, $config);
    }

    /**
     * Read data from a file in JSON format.
     *
     * @param string $type The name of the file without extension.
     *
     * @return mixed|null The decoded data, or null on failure.
     */
    public function readTempData(string $type): mixed
    {
        $filePath = $this->getTempDir() . $type . '.xml';
        
        if (!file_exists($filePath)) {
            throw new FileNotFoundException("Can not find {$filePath}.");
        }
        $reader = new XmlReader();
        $data   = $reader->fromFile($filePath);
        return $data;
    }
}
