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

    public function __construct(array $steps)
    {
        $this->validateSteps($steps);
        $this->steps = $steps;
        $this->stepFile = AppConfig::loadAppConfig()->getTempDir() . DIRECTORY_SEPARATOR . 'last.step';
    }

    public function executeNextStep(): mixed
    {
        $currentStep = $this->getNextStep();

        if (null === $currentStep) {
            return null;
        }

        $result = $currentStep->getStepObject()->execute();

        $this->saveCurrentStep($result->repeat);

        return $result->returnValue;
    }

    private function getNextStep(): ?StepConfig
    {
        $logger = FileLogger::getInstance();
        $prevStepInfo = $this->readStepFile();

        if (!$prevStepInfo || $this->stepsChanged($prevStepInfo)) {
            $logger->debug('Steps changed, start at index 0.');
            $this->currentStepIdx = 0;

            return $this->steps[$this->currentStepIdx];
        }

        $this->currentStepIdx = ($prevStepInfo['last_step_index'] + 1) % count($this->steps);

        if ($this->delayPassed($prevStepInfo)) {
            return $this->steps[$this->currentStepIdx];
        }

        $logger->info('Next execution at: ' . date('Y-m-d H:i:s', $this->nextExecutionTimestamp($prevStepInfo)));

        return null;
    }

    private function saveCurrentStep(bool $repeatCurrentStep): void
    {
        $msg = $repeatCurrentStep ? 'this step again' : 'next step';

        if ($repeatCurrentStep) {
            $this->currentStepIdx = max($this->currentStepIdx - 1, 0);
        }

        FileLogger::getInstance()->info("On the next call, execute {$msg}.");
        $this->writeStepFile();
    }

    private function validateSteps(array $steps): void
    {
        if (count($steps) < 1) {
            throw new \LengthException('At least one step required.');
        }

        foreach ($steps as $step) {
            if (!$step instanceof StepConfig) {
                throw new \UnexpectedValueException('All entries in the array must be of type ' . StepConfig::class);
            }
        }
    }

    private function stepsChanged(array $prevStepInfo): bool
    {
        return $prevStepInfo['step_hash'] !== md5(serialize($this->steps));
    }

    private function delayPassed(array $prevStepInfo): bool
    {
        $nextExecution = $this->nextExecutionTimestamp($prevStepInfo);

        return microtime(true) - $nextExecution >= 0;
    }

    private function nextExecutionTimestamp(array $prevStepInfo): int
    {
        return $prevStepInfo['timestamp'] + $this->steps[$this->currentStepIdx]->delay;
    }

    private function readStepFile(): array
    {
        if (file_exists($this->stepFile) && 0 !== filesize($this->stepFile)) {
            return unserialize(file_get_contents($this->stepFile));
        }

        return [];
    }

    private function writeStepFile(): void
    {
        $content = [
            'last_step_index' => $this->currentStepIdx,
            'timestamp' => time(),
            'step_hash' => md5(serialize($this->steps)),
        ];

        file_put_contents($this->stepFile, serialize($content));
    }
}
