<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Core\FileLogger;
use CMS\PhpBackup\Exceptions\FileNotFoundException;

if (!defined('ABS_PATH')) {
    return;
}

abstract class AbstractStep
{
    public readonly int $delay;
    protected FileLogger $logger;
    protected AppConfig $config;
    protected array $stepData = [];

    /**
     * Set the callback for the step with optional arguments.
     *
     * @param int $delay delay between this and the previous step
     */
    public function __construct(AppConfig $config, int $delay = 0)
    {
        $this->logger = FileLogger::getInstance();
        $this->config = $config;
        $this->delay = $delay;

        try {
            $this->stepData = $this->config->readTempData('StepData');
        } catch (FileNotFoundException) {
            $this->logger->info('No StepData found. Starting empty.');
        }
    }

    public function __serialize(): array
    {
        return [$this->delay];
    }

    /**
     * Execute the callback and return the result.
     *
     * @return StepResult the result of the callback execution
     */
    public function execute(): StepResult
    {
        $class = self::class;
        $this->logger->info("Execute {$class}");
        
        $this->validateStepData();

        $result = $this->_execute();

        if (!empty($this->stepData)){
            $this->config->saveTempData('StepData', $this->stepData);
        }

        return $result;
    }

    private function validateStepData() 
    {
        $requiredKeys = $this->getRequiredStepDataKeys();
    
        $missingKeys = array_diff($requiredKeys, array_keys($this->stepData));
    
        if (!empty($missingKeys)) {
            throw new \InvalidArgumentException("Missing required keys: " . implode(', ', $missingKeys));
        }
    }
    

    abstract protected function _execute(): StepResult;
    abstract protected function getRequiredStepDataKeys(): array;
}
