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
    private readonly int $maxArchiveSize;
    private array $bundles;

    /**
     * CreateBundlesStep constructor.
     *
     * @param AppConfig $config configuration for this step
     */
    public function __construct(AppConfig $config)
    {
        parent::__construct($config);

        $this->srcDir = $this->config->getBackupDirectory()['src'];
        $this->maxArchiveSize = (int) $this->config->getBackupSettings()['maxArchiveSize'];
    }

    protected function _execute(): StepResult
    {
        $this->stepData['bundles'] = [];
        FileBundleCreator::createFileBundles($this->srcDir, $this->maxArchiveSize, $this->stepData['bundles']);

        $backupFolder = TEMP_DIR . 'backup_' . (new \DateTime())->format('Y-m-d_H-i-s') . DIRECTORY_SEPARATOR;
        FileHelper::makeDir($backupFolder);
        $this->stepData['backup_folder'] = $backupFolder;

        return new StepResult($backupFolder, false);
    }

    protected function getRequiredStepDataKeys(): array
    {
        return [];
    }
}
