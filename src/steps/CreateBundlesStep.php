<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\FileBundleCreator;
use CMS\PhpBackup\Helper\FileHelper;

if (!defined('ABS_PATH')) {
    return;
}

final class CreateBundlesStep extends AbstractStep
{
    private readonly string $srcDir;
    private readonly array $excludeDirs;
    private readonly int $maxArchiveSize;
    private array $bundles;

    /**
     * CreateBundlesStep constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->srcDir = $this->config->getBackupDirectory()['src'];
        $this->excludeDirs = $this->config->getBackupDirectory()['exclude'];

        $this->maxArchiveSize = (int) $this->config->getBackupSettings()['maxArchiveSize'];
    }

    protected function getRequiredDataKeys(): array
    {
        return [];
    }

    protected function _execute(): StepResult
    {
        $this->stepData['bundles'] = [];
        FileBundleCreator::createFileBundles(
            $this->srcDir,
            $this->maxArchiveSize,
            $this->stepData['bundles'],
            $this->excludeDirs
        );

        $backupDirectory = TEMP_DIR . $this->config->getAppName() . (new \DateTime())->format('_Y-m-d_H-i-s') . DIRECTORY_SEPARATOR;
        FileHelper::makeDir($backupDirectory);
        $this->stepData['backupDirectory'] = $backupDirectory;

        return new StepResult($backupDirectory, false);
    }
}
