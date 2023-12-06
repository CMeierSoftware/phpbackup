<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use Laminas\Config\Config;
use Laminas\Config\Factory as LaminasConfigFactory;

/**
 * Class ProjectSettings.
 *
 * A singleton class for managing project settings using laminas-config.
 */
class ProjectSettings
{
    private const CONFIG_FILE = CONFIG_DIR . DIRECTORY_SEPARATOR . 'config.json';
    private static ProjectSettings $instance;
    private static Config $config;

    /**
     * Get an instance of the ProjectSettings class.
     */
    public static function getInstance(): ProjectSettings
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$config = new Config(LaminasConfigFactory::fromFile(self::CONFIG_FILE, true));
        }

        return self::$instance;
    }

    /**
     * Save the configuration to the file.
     */
    public function save(): void
    {
        if (self::$config) {
            LaminasConfigFactory::toFile(self::CONFIG_FILE, self::$config);
        }
    }
}
