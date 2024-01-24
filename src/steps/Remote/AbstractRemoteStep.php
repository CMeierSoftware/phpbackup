<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Step\AbstractStep;

if (!defined('ABS_PATH')) {
    return;
}

abstract class AbstractRemoteStep extends AbstractStep
{
    protected readonly AbstractRemoteHandler $remote;

    /**
     * AbstractRemoteStep constructor.
     *
     * @param AbstractRemoteHandler $remoteHandler remote handler for file transfer
     */
    public function __construct(AbstractRemoteHandler $remoteHandler)
    {
        parent::__construct();
        $this->remote = $remoteHandler;
    }
}
