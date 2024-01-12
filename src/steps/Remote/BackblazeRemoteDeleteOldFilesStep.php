<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Remote\Backblaze;

if (!defined('ABS_PATH')) {
    return;
}

final class BackblazeRemoteDeleteOldFilesStep extends AbstractRemoteDeleteOldFilesStep
{
    /**
     * BackblazeRemoteDeleteOldFilesStep constructor.
     *
     * @param AppConfig $config configuration for this step
     */
    public function __construct(AppConfig $config)
    {
        $cfg = $config->getRemoteSettings('backblaze', ['accountId', 'applicationKey', 'bucketName']);
        $remote = new Backblaze($cfg['accountId'], $cfg['applicationKey'], $cfg['bucketName']);
        parent::__construct($remote, $config);
    }
}
