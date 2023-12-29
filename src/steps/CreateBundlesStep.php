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
    private array $bundles;
    private readonly int $maxArchiveSize;

    /**
     * SendRemoteStep constructor.
     *
     * @param int $delay delay in seconds before executing the remote step (optional, default is 0)
     */
    public function __construct(string $srcDir, int $maxArchiveSize, array &$bundles, int $delay = 0)
    {
        parent::__construct($delay);

        $this->srcDir = $srcDir;
        $this->bundles = &$bundles;
        $this->maxArchiveSize = $maxArchiveSize;
    }

    /**
     * Executes the remote step to send backup archives to a remote server.
     *
     * @return StepResult the result of the step execution
     */
    protected function _execute(): StepResult
    {
        FileBundleCreator::createFileBundles($this->srcDir, $this->maxArchiveSize, $this->bundles);

        $backup_folder = TEMP_DIR . 'backup_' . date('Y-m-d_H-i-s') . DIRECTORY_SEPARATOR;
        FileHelper::makeDir($backup_folder);

        return new StepResult($backup_folder, false);
    }
}
