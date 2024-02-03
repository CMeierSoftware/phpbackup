<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Remote\Backblaze;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\Ajax\AbstractAjaxStep;

if (!defined('ABS_PATH')) {
    return;
}

/**
 * Class StepFactory.
 *
 * Factory class for creating instances of AbstractStep with optional remote handler.
 */
final class StepFactory
{
    /**
     * Builds an instance of AbstractStep based on the provided step class and remote handler.
     *
     * @param string $stepClass the class name of the step to be created
     * @param string $remoteHandler the class name of the remote handler (optional)
     *
     * @return AbstractAjaxStep|AbstractStep the created instance of AbstractStep
     *
     * @throws \InvalidArgumentException if the specified step class does not exist
     * @throws \Exception if the specified remote handler method does not exist
     */
    public static function build(string $stepClass, string $remoteHandler = ''): AbstractAjaxStep|AbstractStep
    {
        if (!class_exists($stepClass)) {
            throw new \InvalidArgumentException("Class {$stepClass} does not exist.");
        }

        if (!empty($remoteHandler)) {
            $remote = self::buildRemoteHandler($remoteHandler);

            return new $stepClass($remote);
        }

        return new $stepClass();
    }

    /**
     * Builds an instance of AbstractRemoteHandler based on the provided remote handler class.
     *
     * @param string $remoteHandler the class name of the remote handler
     *
     * @return AbstractRemoteHandler the created instance of AbstractRemoteHandler
     *
     * @throws \Exception if the specified remote handler creation method does not exist
     */
    private static function buildRemoteHandler(string $remoteHandler): AbstractRemoteHandler
    {
        $function = 'create' . ucfirst(strtolower($remoteHandler));

        if (!method_exists(self::class, $function)) {
            throw new \Exception("Method {$function} does not exist in class " . self::class);
        }

        return self::$function();
    }

    public static function getRemoteClasses(array $remoteHandler): array
    {
        $namespace = substr(AbstractRemoteHandler::class, 0, strrpos(AbstractRemoteHandler::class, '\\') + 1);
        $remoteClasses = array_map(
            static function (string $cls) use($namespace) : string {
                $p = explode('\\', $cls);
                return $namespace . ucfirst(strtolower(end($p)));
            },
            $remoteHandler
        );
        return array_filter($remoteClasses, 'class_exists');
    }

    /**
     * Creates a Local remote handler instance.
     *
     * @return Local the created instance of Local remote handler
     */
    private static function createLocal(): Local
    {
        $cfg = AppConfig::loadAppConfig()->getRemoteSettings('local', ['rootDir']);

        return new Local(AppConfig::toAbsolutePath($cfg['rootDir']));
    }

    /**
     * Creates a Backblaze remote handler instance.
     *
     * @return Backblaze the created instance of Backblaze remote handler
     */
    private static function createBackblaze(): Backblaze
    {
        $cfg = AppConfig::loadAppConfig()->getRemoteSettings('backblaze', ['accountId', 'applicationKey', 'bucketName']);

        return new Backblaze($cfg['accountId'], $cfg['applicationKey'], $cfg['bucketName']);
    }
}
