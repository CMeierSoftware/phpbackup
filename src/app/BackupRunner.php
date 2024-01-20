<?php

declare(strict_types=1);

namespace CMS\PhpBackup\App;

use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Step\CleanUpStep;
use CMS\PhpBackup\Step\CreateBundlesStep;
use CMS\PhpBackup\Step\DatabaseBackupStep;
use CMS\PhpBackup\Step\DirectoryBackupStep;
use CMS\PhpBackup\Step\Remote\AbstractRemoteDeleteOldFilesStep;
use CMS\PhpBackup\Step\Remote\AbstractRemoteSendFileStep;
use CMS\PhpBackup\Step\StepConfig;

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
        $steps[] = new StepConfig(DirectoryBackupStep::class);
        $steps[] = new StepConfig(DatabaseBackupStep::class);

        $steps = array_merge($steps, $this->getRemoteStepsFor(AbstractRemoteSendFileStep::class));
        $steps = array_merge($steps, $this->getRemoteStepsFor(AbstractRemoteDeleteOldFilesStep::class));

        $steps[] = new StepConfig(CleanUpStep::class);

        return $steps;
    }
}
