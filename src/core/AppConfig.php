<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Helper\FileHelper;
use Laminas\Config\Config;
use Laminas\Config\Exception\UnprocessableConfigException;
use Laminas\Config\Factory as LaminasConfigFactory;
use Laminas\Config\Reader\Xml as XmlReader;
use Laminas\Config\Writer\Xml as XmlWriter;

if (!defined('ABS_PATH')) {
    return;
}
final class AppConfig
{
    private const TMP_DIR = 'temp_';
    private const TEMP_DATA_KEY_SEP = '_-_';
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
            throw new UnprocessableConfigException("The config {$configFile} is not formatted correctly. Its empty or not containing elements.");
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
    public function saveTempData(string $type, array &$data): void
    {
        $this->processDataForSave($data);

        $sanitizedType = str_replace([DIRECTORY_SEPARATOR, '\\', '/'], '_', $type);
        $filePath = $this->getTempDir() . $sanitizedType . '.xml';
        FileLogger::getInstance()->info("Will write tempData to '{$filePath}'.");

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
        // / TODO if file empty, its a string
        $this->processDataForRead($data);

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
        if (isset($this->config['backup']['directory'])) {
            if (str_contains($this->config['backup']['directory']['src'], '..')) {
                $this->config['backup']['directory']['src'] = realpath(CONFIG_DIR . $this->config['backup']['directory']['src']);
                if (!$this->config['backup']['directory']['src'] || !file_exists($this->config['backup']['directory']['src'])) {
                    throw new FileNotFoundException('Directory source for backup does not exists. It must be relative from config dir.');
                }
            }

            return $this->config['backup']['directory'];
        }

        return null;
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

    public function getDefinedRemoteClasses(): array
    {
        $remoteClasses = $this->getRemoteSettings();

        if (null === $remoteClasses) {
            return [];
        }
        $remoteClasses = array_keys($remoteClasses);
        $remoteClasses = array_map(static fn ($cls) => 'CMS\PhpBackup\Remote\\' . ucfirst($cls), $remoteClasses);

        return array_filter($remoteClasses, 'class_exists');
    }

    // Helper function to check if an array has numeric keys
    private function hasNumericKeys(array &$array): bool
    {
        foreach (array_keys($array) as $key) {
            if (is_numeric($key)) {
                return true;
            }
        }

        return false;
    }

    // Recursive function for saving data
    private function processDataForSave(array &$data): void
    {
        foreach (array_keys($data) as $key) {
            $node = &$data[$key];
            if (!is_array($node) || empty($node)) {
                continue;
            }
            // only change keys when values are also arrays
            if (is_array($node[array_key_first($node)]) && $this->hasNumericKeys($node)) {
                $node = array_combine(
                    array_map(static fn ($k) => $key . self::TEMP_DATA_KEY_SEP . $k, array_keys($node)),
                    $node
                );
            }
            $this->processDataForSave($node);
        }
    }

    // Recursive function for reading data
    private function processDataForRead(array &$data): void
    {
        foreach (array_keys($data) as $key) {
            $node = &$data[$key];
            if (!is_array($node) || empty($node)) {
                continue;
            }

            $this->processDataForRead($data[$key]);

            $childKeys = array_keys($node);
            if (is_string($childKeys[0]) && 0 === strpos($childKeys[0], $key . self::TEMP_DATA_KEY_SEP)) {
                $node = array_combine(
                    array_map(static fn ($k) => (int) str_replace($key . self::TEMP_DATA_KEY_SEP, '', $k), array_keys($node)),
                    $node
                );
            }
        }
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
