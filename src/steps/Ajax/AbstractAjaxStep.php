<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step\Ajax;

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
    protected array $postData = [];

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
    public function execute(): StepResult
    {
        $this->logger->info('Execute ' . $this->classDetails());

        $this->parsePostData();

        return $this->_execute();
    }

    public function setPostData(array $postData): void
    {
        $this->postData = $postData;
    }

    abstract protected function sanitizeData(): void;

    private function parsePostData(): void
    {
        $missingKeys = array_diff($this->getRequiredDataKeys(), array_keys($this->postData));

        if (!empty($missingKeys)) {
            throw new \InvalidArgumentException('Missing required keys: ' . implode(', ', $missingKeys));
        }

        $this->sanitizeData();
    }
}
