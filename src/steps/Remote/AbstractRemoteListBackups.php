<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Remote;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;
use CMS\PhpBackup\Step\AbstractStep;
use CMS\PhpBackup\Step\StepResult;

if (!defined('ABS_PATH')) {
    return;
}

abstract class AbstractRemoteListBackups extends AbstractStep
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

    protected function getRequiredStepDataKeys(): array
    {
        return [];
    }
}
