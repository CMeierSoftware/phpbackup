<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\AppConfig;

if (!defined('ABS_PATH')) {
    return;
}

final class ListRemoteHandlerStep extends AbstractStep
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
        $result = AppConfig::loadAppConfig()->getDefinedRemoteHandler();
        $result = array_map(static fn (string $name): array => ['id' => $name, 'label' => ucfirst($name)], $result);

        return new StepResult($result, false);
    }

    protected function sanitizeData(): void {}
}
