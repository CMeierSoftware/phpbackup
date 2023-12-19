<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Helper\FileHelper;
use Laminas\Config\Config;
use Laminas\Config\Factory as LaminasConfigFactory;
use Laminas\Config\Reader\Xml as XmlReader;
use Laminas\Config\Writer\Xml as XmlWriter;

if (!defined('ABS_PATH')) {
    return;
}
final class AppConfig
{
    private const TMP_DIR = 'temp_';
    private array $config;
    private readonly string $configFile;
    private readonly string $tempDir;

    /**
     * AppConfig constructor.
     *
     * @param string $configFile the path of the configuration file
     * @param string $tempDir the path of the temporary directory
     */
    private function __construct(string $configFile, string $tempDir)
    {
        $this->configFile = $configFile;
        $cfg = LaminasConfigFactory::fromFile($configFile);
        if (empty($cfg) || !is_array($cfg)) {
            throw new \Laminas\Config\Exception\UnprocessableConfigException('');
        }
        $this->config = $cfg;
        $this->tempDir = $tempDir;
    }

    /**
     * Loads the application configuration for a specific app.
     *
     * @param string $app the app name
     *
     * @return self an instance of the AppConfig class
     *
     * @throws FileNotFoundException if the configuration file does not exist
     */
    public static function loadAppConfig(string $app): self
    {
        $config_file = CONFIG_DIR . $app . '.xml';
        $tempDir = CONFIG_DIR . self::TMP_DIR . $app;

        if (!file_exists($config_file)) {
            throw new FileNotFoundException("Configuration file does not exist: {$config_file}");
        }

        return new self($config_file, $tempDir);
    }

    /**
     * Returns the path of the temporary directory.
     *
     * @return string the path of the temporary directory
     */
    public function getTempDir(): string
    {
        $this->createTempDir();

        return $this->tempDir . DIRECTORY_SEPARATOR;
    }

    /**
     * Saves temporary data of a specific type.
     *
     * @param string $type the type of data
     * @param array $data the data to be saved
     */
    public function saveTempData(string $type, array $data): void
    {
        $sanitizedType = str_replace([DIRECTORY_SEPARATOR, '\\', '/'], '_', $type);
        $filePath = $this->getTempDir() . $sanitizedType . '.xml';
        FileLogger::getInstance()->info("Will write tempData to '{$filePath}'.");

        if (isset($data['bundles']) && is_array($data['bundles'])) {
            // Iterate through each bundle and create new keys like 'bundles_0', 'bundles_1', etc.
            foreach ($data['bundles'] as $index => $bundle) {
                $data["bundles_{$index}"] = $bundle;
            }

            // Remove the original 'bundles' key
            unset($data['bundles']);
        }

        $config = new Config($data, false);
        $writer = new XmlWriter();
        $writer->toFile($filePath, $config);

        FileLogger::getInstance()->info("Wrote tempData to '{$filePath}'.");
    }

    /**
     * Reads and returns temporary data of a specific type.
     *
     * @param string $type the type of data
     *
     * @return array the temporary data
     *
     * @throws FileNotFoundException if the data file does not exist
     */
    public function readTempData(string $type): array
    {
        $filePath = $this->getTempDir() . $type . '.xml';

        if (!file_exists($filePath)) {
            throw new FileNotFoundException("Can not find {$filePath}.");
        }
        $reader = new XmlReader();

        FileLogger::getInstance()->info("Read tempData from '{$filePath}'.");

        $data = $reader->fromFile($filePath);

        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'bundles_')) {
                $idx = (int) explode('_', $key)[1];
                $data['bundles'][$idx] = $data[$key];
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Returns the backup database settings from the configuration file.
     *
     * @return null|array the backup database settings
     */
    public function getBackupDatabase(): ?array
    {
        return isset($this->config['backup']['database']) ? $this->config['backup']['database'] : null;
    }

    /**
     * Returns the backup directory settings from the configuration file.
     *
     * @return null|array the backup directory settings
     */
    public function getBackupDirectory(): ?array
    {
        return isset($this->config['backup']['directory']) ? $this->config['backup']['directory'] : null;
    }

    /**
     * Returns the backup settings from the configuration file.
     *
     * @return null|array the backup settings
     */
    public function getBackupSettings(): ?array
    {
        return isset($this->config['backup']['settings']) ? $this->config['backup']['settings'] : null;
    }

    /**
     * Returns the remote settings from the configuration file.
     *
     * @return null|array the remote settings
     */
    public function getRemoteSettings(): ?array
    {
        return isset($this->config['remote']) ? $this->config['remote'] : null;
    }

    /**
     * Creates the temporary directory if it does not exist.
     */
    private function createTempDir(): void
    {
        if (!file_exists($this->tempDir)) {
            FileHelper::makeDir($this->tempDir);
        }
    }
}
