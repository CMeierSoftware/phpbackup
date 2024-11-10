<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

if (!defined('ABS_PATH')) {
    return;
}

final class ListBackupsStep extends AbstractStep
{
    protected function getRequiredDataKeys(): array
    {
        return [];
    }

    /**
     * Executes the remote step to send backup archives to a remote server.
     *
     * @return StepResult the result of the step execution
     */
    protected function _execute(): StepResult
    {
        $dirs = [];
        $result = [];

        try {
            $this->remote->connect();
            $dirs = $this->remote->dirList('.');
        } finally {
            $this->remote->disconnect();
        }

        foreach ($dirs as $dir) {
            if (preg_match('/^.*_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})$/', $dir, $matches)) {
                $dateTime = \DateTime::createFromFormat('Y-m-d_H-i-s', $matches[1]);
                $result[] = ['id' => $dir, 'label' => $dateTime->format('Y-m-d H:i:s')];
            }
        }

        return new StepResult($result, false);
    }

    protected function sanitizeData(): void {}
}
