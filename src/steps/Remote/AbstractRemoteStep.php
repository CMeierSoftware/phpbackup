<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Step\AbstractStep;

if (!defined('ABS_PATH')) {
    return;
}

abstract class AbstractRemoteStep extends AbstractStep
{
    private readonly AbstractRemoteHandler $remote;

    /**
     * SendRemoteStep constructor.
     *
     * @param AbstractRemoteHandler $remoteHandler remote handler for file transfer
     * @param AppConfig $config configuration for this step
     */
    public function __construct(AbstractRemoteHandler $remoteHandler, AppConfig $config)
    {
        parent::__construct($config);
        $this->remote = $remoteHandler;
    }
}
