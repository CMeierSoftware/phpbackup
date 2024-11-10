<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\StepFactory;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;

if (!defined('ABS_PATH')) {
    return;
}

final class DeleteOldFilesStep extends AbstractStep
{
    private readonly int $keepBackupDays;
    private readonly int $keepBackupAmount;

    /**
     * DeleteOldFilesStep constructor.
     *
     * @param AbstractRemoteHandler $remoteHandler remote handler for file transfer
     */
    public function __construct(AbstractRemoteHandler $remoteHandler)
    {
        parent::__construct($remoteHandler);
        $remoteClassName = strtolower(StepFactory::extractClassName($remoteHandler::class));
        $this->keepBackupDays = (int) $this->config->getRemoteSettings($remoteClassName)['keepBackupDays'];
        $this->keepBackupAmount = (int) $this->config->getRemoteSettings($remoteClassName)['keepBackupAmount'];
    }

    protected function getRequiredDataKeys(): array
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
        $result = $this->remote->deleteOld('', $this->keepBackupDays, $this->keepBackupAmount);

        return new StepResult($result, false);
    }

    protected function sanitizeData(): void {}
}
