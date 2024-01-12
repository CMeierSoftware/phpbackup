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
        $id = $config->getRemoteSettings()['backblaze']['accountId'];
        $appKey = $config->getRemoteSettings()['backblaze']['applicationKey'];
        $bucket = $config->getRemoteSettings()['backblaze']['bucketName'];
        $remote = new Backblaze($id, $appKey, $bucket);
        parent::__construct($remote, $config);
    }
}
