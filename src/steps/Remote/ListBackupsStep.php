<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Step\StepResult;

if (!defined('ABS_PATH')) {
    return;
}

final class ListBackupsStep extends AbstractRemoteStep
{
    /**
     * SendRemoteStep constructor.
     *
     * @param AbstractRemoteHandler $remoteHandler remote handler for file transfer
     */
    public function __construct(AbstractRemoteHandler $remoteHandler)
    {
        parent::__construct($remoteHandler);
    }

    public function getRequiredDataKeys(): array
    {
        return [];
    }

    /**
     * Executes the remote step to send backup archives to a remote server.
     *
     * @return StepResult the result of the step execution
     */
    protected function _execute(): StepResult
    {
        $this->remote->connect();
        $result = $this->remote->dirList('.');

        return new StepResult($result, false);
    }
}
