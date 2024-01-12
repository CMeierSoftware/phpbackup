<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Remote\Local;

if (!defined('ABS_PATH')) {
    return;
}

final class LocalRemoteSendFileStep extends AbstractRemoteSendFileStep
{
    /**
     * LocalRemoteSendFileStep constructor.
     *
     * @param AppConfig $config configuration for this step
     */
    public function __construct(AppConfig $config)
    {
        $rootDir = $this->config->toAbsolutePath($config->getRemoteSettings()['local']['rootDir']);
        $remote = new Local($rootDir);
        parent::__construct($remote, $config);
    }
}
