<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Ajax;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Step\AbstractStep;
use CMS\PhpBackup\Step\StepResult;

if (!defined('ABS_PATH')) {
    return;
}

/**
 * Abstract base class for implementing steps in a process.
 */
abstract class AbstractAjaxStep extends AbstractStep
{
    protected readonly FileLogger $logger;
    protected readonly AppConfig $config;

    /**
     * AbstractStep constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the callback and return the result.
     *
     * @return StepResult the result of the callback execution
     */
    public function execute(array $postData): StepResult
    {
        $class = $this::class;
        $this->logger->info("Execute {$class}");

        $postData = $this->parsePostData($postData);

        return $this->_execute($postData);
    }

    /**
     * Abstract method to be implemented by child classes.
     *
     * @return StepResult the result of the callback execution
     */
    abstract protected function _execute(array $postData): StepResult;

    abstract protected function sanitizeData(array $postData): array;

    private function parsePostData(array $postData): array
    {
        $missingKeys = array_diff($this->getRequiredDataKeys(), $postData);

        if (!empty($missingKeys)) {
            throw new \InvalidArgumentException('Missing required keys: ' . implode(', ', $missingKeys));
        }

        return $this->sanitizeData($postData);
    }
}
