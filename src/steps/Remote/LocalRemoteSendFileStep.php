<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Remote\Local;
use CMS\PhpBackup\Step\AbstractRemoteSendFileStep;

if (!defined('ABS_PATH')) {
    return;
}

final class LocalRemoteSendFileStep extends AbstractRemoteSendFileStep
{
    /**
     * LocalRemoteSendFileStep constructor.
     *
     * @param AppConfig $config configuration for this step
     * @param int $delay delay between this and the previous step
     */
    public function __construct(AppConfig $config, int $delay = 0)
    {
        $remote = new Local($config->getRemoteSettings()['local']['rootDir']);
        parent::__construct($remote, $config, $delay);
    }
}
