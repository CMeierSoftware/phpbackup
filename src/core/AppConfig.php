<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use CMS\PhpBackup\Exceptions\FileNotFoundException;
use CMS\PhpBackup\Helper\FileHelper;
use Laminas\Config\Config;
use Laminas\Config\Exception\UnprocessableConfigException;
use Laminas\Config\Factory as LaminasConfigFactory;
use Laminas\Config\Reader\Json as JsonReader;
use Laminas\Config\Writer\Json as JsonWriter;

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
        $filePath = $this->getTempDataFilePath($type);

        $config = new Config($data, false);
        $writer = new JsonWriter();
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
        $filePath = $this->getTempDataFilePath($type);

        if (!file_exists($filePath)) {
            throw new FileNotFoundException("Can not find {$filePath}.");
        }
        $reader = new JsonReader();

        FileLogger::getInstance()->info("Read tempData from '{$filePath}'.");

        $data = is_array($result = $reader->fromFile($filePath)) ? $result : [];

        return $data;
    }

    /**
     * Returns the backup database settings from the configuration file.
     *
     * @return array the backup database settings
     */
    public function getBackupDatabase(): array
    {
        return isset($this->config['backup']['database']) ? $this->config['backup']['database'] : [];
    }

    /**
     * Returns the backup directory settings from the configuration file.
     *
     * @return array the backup directory settings
     */
    public function getBackupDirectory(): array
    {
        if (isset($this->config['backup']['directory'])) {
            $cfg = $this->config['backup']['directory'];

            $cfg['src'] = $this->toAbsolutePath($cfg['src']);

            $cfg['exclude'] = array_map(
                fn ($item): string => $this->toAbsolutePath($item), 
                $cfg['exclude']
            );

            $cfg['exclude'] = array_filter(
                $cfg['exclude'],
                static fn($item) => str_starts_with($item, $cfg['src'])
            );

            return $cfg;
        }

        return [];
    }

    /**
     * Returns the backup settings from the configuration file.
     *
     * @return array the backup settings
     */
    public function getBackupSettings(): array
    {
        return isset($this->config['backup']['settings']) ? $this->config['backup']['settings'] : [];
    }

    /**
     * Returns the remote settings from the configuration file.
     *
     * @param string $type (Optional) The type of remote settings to retrieve
     * @param array $requiredSettings (Optional) An array of required settings to check for existence
     *
     * @return array The remote settings. If $type is provided, returns settings for that type; otherwise, returns all remote settings.
     *
     * @throws \InvalidArgumentException if any required setting is not present in the configuration
     */
    public function getRemoteSettings(string $type = '', array $requiredSettings = []): array
    {
        $remoteSettings = $this->config['remote'] ?? [];

        if (!empty($type)) {
            $remoteSettings = $remoteSettings[$type] ?? [];

            $missingSettings = array_diff_key(array_flip($requiredSettings), $remoteSettings);
            if (!empty($missingSettings)) {
                $missingSettingsList = implode(', ', array_keys($missingSettings));

                throw new \InvalidArgumentException("Required setting(s) '{$missingSettingsList}' is/are missing in the remote configuration.");
            }
        }

        return $remoteSettings;
    }

    public function getDefinedRemoteClasses(string $baseClass): array
    {
        $remoteClasses = $this->getRemoteSettings();

        if (null === $remoteClasses) {
            return [];
        }
        $remoteClasses = array_keys($remoteClasses);
        $remoteClasses = array_map(
            static fn ($cls) => str_replace('Abstract', ucfirst($cls), $baseClass),
            $remoteClasses
        );

        return array_filter($remoteClasses, 'class_exists');
    }

    public function toAbsolutePath($relativePath, $baseDir = CONFIG_DIR): string
    {
        $regex = '\\/\\' . DIRECTORY_SEPARATOR;
        $parts = preg_split("/[{$regex}]/", $relativePath);

        $absoluteParts = preg_split("/[{$regex}]/", $baseDir);
        $absoluteParts = array_filter($absoluteParts);
        $isWin = 1 === preg_match('/^[A-Za-z]:/', $absoluteParts[0]);

        foreach ($parts as $part) {
            if ('..' === $part) {
                array_pop($absoluteParts);
            } elseif ('.' !== $part && '' !== $part) {
                $absoluteParts[] = $part;
            }
        }

        $path = trim(implode(DIRECTORY_SEPARATOR, $absoluteParts), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $isWin ? $path : DIRECTORY_SEPARATOR . $path;
    }

    public function getAppName(): string
    {
        return pathinfo($this->configFile, PATHINFO_FILENAME);
    }

    private function getTempDataFilePath(string $type): string
    {
        $sanitizedType = str_replace([DIRECTORY_SEPARATOR, '\\', '/'], '_', $type);

        return $this->getTempDir() . $sanitizedType . '.json';
    }

    private function hasNumericKeys(array &$array): bool
    {
        foreach (array_keys($array) as $key) {
            if (is_numeric($key)) {
                return true;
            }
        }

        return false;
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
