<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Helper\FileHelper;

if (!defined('ABS_PATH')) {
    return;
}

final class CleanUpStep extends AbstractStep
{
    protected function _execute(): StepResult
    {
        FileHelper::deleteDirectory($this->stepData['backupDirectory']);

        $this->stepData = [];

        return new StepResult('Backup process done.', false);
    }

    protected function getRequiredStepDataKeys(): array
    {
        return ['backupDirectory'];
    }
}
