<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Helper\FileBundleCreator;
use CMS\PhpBackup\Helper\FileHelper;
use CMS\PhpBackup\Remote\AbstractRemoteHandler;

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
    public function __construct(?AbstractRemoteHandler $remoteHandler)
    {
        parent::__construct(null);

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
        $this->data['bundles'] = [];
        FileBundleCreator::createFileBundles(
            $this->srcDir,
            $this->maxArchiveSize,
            $this->data['bundles'],
            $this->excludeDirs
        );

        $backupDirectory = TEMP_DIR . $this->config->getAppName() . (new \DateTime())->format('_Y-m-d_H-i-s') . DIRECTORY_SEPARATOR;
        FileHelper::makeDir($backupDirectory);
        $this->data['backupDirectory'] = $backupDirectory;

        return new StepResult($backupDirectory, false);
    }

    protected function sanitizeData(): void {}
}
