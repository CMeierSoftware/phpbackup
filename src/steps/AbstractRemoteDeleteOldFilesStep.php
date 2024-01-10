<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;

if (!defined('ABS_PATH')) {
    return;
}

abstract class AbstractRemoteDeleteOldFilesStep extends AbstractStep
{
    private readonly AbstractRemoteHandler $remote;
    private readonly int $keepBackupDays;
    private readonly int $keepBackupAmount;

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
