<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Step\StepResult;

if (!defined('ABS_PATH')) {
    return;
}

final class DeleteOldFilesStep extends AbstractRemoteStep
{
    private readonly int $keepBackupDays;
    private readonly int $keepBackupAmount;

    /**
     * SendRemoteStep constructor.
     *
     * @param AbstractRemoteHandler $remoteHandler remote handler for file transfer
     */
    public function __construct(AbstractRemoteHandler $remoteHandler)
    {
        parent::__construct($remoteHandler);

        $this->keepBackupDays = (int) $this->config->getBackupSettings()['keepBackupDays'];
        $this->keepBackupAmount = (int) $this->config->getBackupSettings()['keepBackupAmount'];
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

    protected function getRequiredStepDataKeys(): array
    {
        return [];
    }
}
