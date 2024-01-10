<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\AbstractRemoteDeleteOldFilesStep;

if (!defined('ABS_PATH')) {
    return;
}

final class LocalRemoteDeleteOldFilesStep extends AbstractRemoteDeleteOldFilesStep
{
    /**
     * LocalRemoteDeleteOldFilesStep constructor.
     *
     * @param AppConfig $config configuration for this step
     */
    public function __construct(AppConfig $config)
    {
        $remote = new Local($config->getRemoteSettings()['local']['rootDir']);
        parent::__construct($remote, $config);
    }
}
