<?php

declare(strict_types=1);

namespace CMS\PhpBackup\App;

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
        $remoteHandler = array_map('ucfirst', array_keys($this->config->getRemoteSettings()));

        $steps[] = new StepConfig(CreateBundlesStep::class);
        $steps[] = new StepConfig(DirectoryBackupStep::class);
        $steps[] = new StepConfig(DatabaseBackupStep::class);

        foreach ($remoteHandler as $handler) {
            $class = str_replace('Abstract', $handler, AbstractRemoteSendFileStep::class);
            $steps[] = new StepConfig($class);
        }
        foreach ($remoteHandler as $handler) {
            $class = str_replace('Abstract', $handler, AbstractRemoteDeleteOldFilesStep::class);
            $steps[] = new StepConfig($class);
        }

        $steps[] = new StepConfig(CleanUpStep::class);

        return $steps;
    }
}
