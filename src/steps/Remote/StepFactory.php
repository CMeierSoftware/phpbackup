<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Remote\Backblaze;
use CMS\PhpBackup\Remote\Local;

if (!defined('ABS_PATH')) {
    return;
}

final class StepFactory
{
    public static function build(string $stepClass, string $remoteHandler, AppConfig $config)
    {
        if (!class_exists($stepClass)) {
            throw new \InvalidArgumentException("Class {$stepClass} does not exist");
        }

        $remote = self::buildRemoteHandler($remoteHandler, $config);

        return new $stepClass($remote, $config);
    }

    private static function buildRemoteHandler(string $remoteHandler, AppConfig $config): AbstractRemoteHandler
    {
        $remoteHandler = ucfirst(strtolower($remoteHandler));
        $function = 'create' . $remoteHandler;

        if (!method_exists(self::class, $function)) {
            throw new \Exception("Method {$function} does not exist in class " . self::class);
        }

        return self::$function($config);
    }

    private static function createLocal(AppConfig $config): Local
    {
        $cfg = $config->getRemoteSettings('local', ['rootDir']);

        return new Local($config->toAbsolutePath($cfg['rootDir']));
    }

    private static function createBackblaze(AppConfig $config): Backblaze
    {
        $cfg = $config->getRemoteSettings('backblaze', ['accountId', 'applicationKey', 'bucketName']);

        return new Backblaze($cfg['accountId'], $cfg['applicationKey'], $cfg['bucketName']);
    }
}
