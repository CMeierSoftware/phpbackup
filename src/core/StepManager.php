<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

use CMS\PhpBackup\Step\StepConfig;

if (!defined('ABS_PATH')) {
    return;
}

final class StepManager
{
    private readonly string $stepFile;
    private readonly array $steps;
    private ?int $currentStepIdx = null;
    private AppConfig $config;

    /**
     * Constructs a StepManager instance with an array of possible steps.
     *
     * @param array $steps an array of possible steps with their names and relative delay in seconds
     *
     * @throws \LengthException if the array of steps is empty
     */
    public function __construct(array $steps, AppConfig $config)
    {
        if (count($steps) < 1) {
            throw new \LengthException('At least one step required.');
        }

        foreach ($steps as $step) {
            if (!$step instanceof StepConfig) {
                throw new \UnexpectedValueException('All entries in the array must be of type ' . StepConfig::class);
            }
        }

        $this->steps = $steps;
        $this->config = $config;
        $this->stepFile = $this->config->getTempDir() . DIRECTORY_SEPARATOR . 'last.step';
    }

    /**
     * Executes the next step and returns the result.
     *
     * @return mixed the result of executing the next step
     */
    public function executeNextStep(): mixed
    {
        $currentStep = $this->getNextStep();

        if (null === $currentStep) {
            return null;
        }

        $stepObj = $currentStep->getStepObject($this->config);

        $result = $stepObj->execute();

        $this->saveCurrentStep($result->repeat);

        return $result->returnValue;
    }

    /**
     * Determines the next step to be executed based on the previous step information.
     *
     * @return null|StepConfig the next step to be executed, or null if there is no next step
     */
    private function getNextStep(): ?StepConfig
    {
        $prevStepInfo = false;
        $logger = FileLogger::getInstance();
        if (file_exists($this->stepFile) && 0 !== filesize($this->stepFile)) {
            $prevStepInfo = unserialize(file_get_contents($this->stepFile));
        }

        // Check if there is no previous step information or if steps have changed
        if (!$prevStepInfo || $prevStepInfo['step_hash'] !== md5(serialize($this->steps))) {
            $logger->info('Steps changed, start at index 0.');
            $this->currentStepIdx = 0;

            return $this->steps[0];
        }

        $this->currentStepIdx = ((int) $prevStepInfo['last_step_index'] + 1) % count($this->steps);

        // Check if the delay for the current step has passed
        $nextExecution = ($prevStepInfo['timestamp'] + $this->steps[$this->currentStepIdx]->delay);
        if (microtime(true) -$nextExecution >= 0) {
            return $this->steps[$this->currentStepIdx];
        } else {
            $logger->info('Next execution at: ' . date("Y-m-d H:i:s", $nextExecution));
        }

        return null;
    }

    /**
     * Records the completion of the current step.
     */
    private function saveCurrentStep(bool $repeatCurrentStep): void
    {
        if ($repeatCurrentStep) {
            FileLogger::getInstance()->info('On next call, execute this step again.');
            $this->currentStepIdx = ($this->currentStepIdx < 0 ? count($this->steps) : $this->currentStepIdx) - 1;
        } else {
            FileLogger::getInstance()->info('On next call, execute next step.');
        }

        $content = [
            'last_step_index' => $this->currentStepIdx,
            'timestamp' => time(),
            'step_hash' => md5(serialize($this->steps)),
        ];

        file_put_contents($this->stepFile, serialize($content));
    }
}
