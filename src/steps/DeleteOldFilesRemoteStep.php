<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Remote\AbstractRemoteHandler;

if (!defined('ABS_PATH')) {
    return;
}

final class DeleteOldFilesRemoteStep extends AbstractStep
{
    private readonly AbstractRemoteHandler $remote;
    private readonly int $keepBackupDays;
    private readonly int $keepBackupAmount;

    /**
     * SendRemoteStep constructor.
     *
     * @param AbstractRemoteHandler $remoteHandler remote handler for file transfer
     * @param int $keepBackupDays local directory containing backup files
     * @param int $keepBackupAmount array of backup archives to be sent
     * @param int $delay delay in seconds before executing the remote step (optional, default is 0)
     */
    public function __construct(AbstractRemoteHandler $remoteHandler, int $keepBackupDays, int $keepBackupAmount, int $delay = 0)
    {
        parent::__construct($delay);

        $this->remote = $remoteHandler;
        $this->keepBackupDays = $keepBackupDays;
        $this->keepBackupAmount = $keepBackupAmount;
    }

    /**
     * Executes the remote step to send backup archives to a remote server.
     *
     * @return StepResult the result of the step execution
     */
    protected function _execute(): StepResult
    {
        $this->remote->connect();
        $result = $this->remote->deleteOld('', $this->keepBackupDays, $this->keepBackupAmount);

        return new StepResult($result, false);
    }
}
