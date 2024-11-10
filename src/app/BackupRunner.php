<?php

declare(strict_types=1);

namespace CMS\PhpBackup\App;

use CMS\PhpBackup\Core\StepConfig;
use CMS\PhpBackup\Helper\FileLogger;
use CMS\PhpBackup\Step\BackupDatabaseStep;
use CMS\PhpBackup\Step\BackupDirectoryStep;
use CMS\PhpBackup\Step\CleanUpStep;
use CMS\PhpBackup\Step\CreateBundlesStep;
use CMS\PhpBackup\Step\DeleteOldFilesStep;
use CMS\PhpBackup\Step\SendFileStep;

if (!defined('ABS_PATH')) {
    return;
}

class BackupRunner extends AbstractRunner
{
    protected function setupSteps(): array
    {
        $steps = [];
        $repeatDelay = (int) $this->config->getBackupSettings()['executeEveryDays'];
        $repeatDelaySecs = $repeatDelay * 24 * 60 * 60;

        FileLogger::getInstance()->debug("Repeat delay {$repeatDelay} day(s) or {$repeatDelaySecs} seconds.");

        $steps[] = new StepConfig(CreateBundlesStep::class, $repeatDelaySecs);
        $steps[] = new StepConfig(BackupDirectoryStep::class);
        $steps[] = new StepConfig(BackupDatabaseStep::class);

        $steps = array_merge($steps, $this->getRemoteStepsFor(SendFileStep::class));
        $steps = array_merge($steps, $this->getRemoteStepsFor(DeleteOldFilesStep::class));

        $steps[] = new StepConfig(CleanUpStep::class);

        return $steps;
    }
}
