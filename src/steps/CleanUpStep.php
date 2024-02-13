<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;

if (!defined('ABS_PATH')) {
    return;
}

final class CleanUpStep extends AbstractStep
{
    /**
     * CreateBundlesStep constructor.
     */
    public function __construct(?AbstractRemoteHandler $remoteHandler)
    {
        parent::__construct(null);
    }

    protected function getRequiredDataKeys(): array
    {
        return ['backupDirectory'];
    }

    protected function _execute(): StepResult
    {
        FileHelper::deleteDirectory($this->data['backupDirectory']);

        $this->data = [];

        return new StepResult('Backup process done.', false);
    }

    protected function sanitizeData(): void {}
}
