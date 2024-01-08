<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\FileBundleCreator;
use CMS\PhpBackup\Helper\FileHelper;

if (!defined('ABS_PATH')) {
    return;
}

final class CreateBundlesStep extends AbstractStep
{
    private readonly string $srcDir;
    private array $bundles;
    private readonly int $maxArchiveSize;

    /**
     * SendRemoteStep constructor.
     *
     * @param int $delay delay in seconds before executing the remote step (optional, default is 0)
     */
    public function __construct(AppConfig $config, int $delay = 0)
    {
        parent::__construct($config, $delay);

        $this->srcDir = $this->config->getBackupDirectory()['src'];
        $this->maxArchiveSize = (int) $this->config->getBackupSettings()['maxArchiveSize'];
    }

    /**
     * Executes the remote step to send backup archives to a remote server.
     *
     * @return StepResult the result of the step execution
     */
    protected function _execute(): StepResult
    {
        $this->stepData['bundles'] = [];
        FileBundleCreator::createFileBundles($this->srcDir, $this->maxArchiveSize, $this->stepData['bundles']);

        $backupFolder = TEMP_DIR . 'backup_' . date('Y-m-d_H-i-s') . DIRECTORY_SEPARATOR;
        FileHelper::makeDir($backupFolder);
        $this->stepData['backup_folder'] = $backupFolder;

        return new StepResult($backupFolder, false);
    }

    protected function getRequiredStepDataKeys(): array
    {
        return [];
    }
}
