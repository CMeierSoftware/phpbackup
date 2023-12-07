<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Core;

if (!defined('ABS_PATH')) {
    return;
}
use CMS\PhpBackup\Core\Step;
class StepManager
{
    private const STEP_FILE = CONFIG_DIR . DIRECTORY_SEPARATOR . 'last.step';
    private readonly array $steps;
    private ?int $currentStepIdx = null;

    /**
     * Constructs a StepManager instance with an array of possible steps.
     *
     * @param array $steps An array of possible steps with their names and relative delay in seconds.
     * @throws \LengthException If the array of steps is empty.
     */
    public function __construct(array $steps)
    {
        if (count($steps) < 1) {
            throw new \LengthException('At least one step required.');
        }

        $this->steps = $steps;
    }

    /**
     * Executes the next step and returns the result.
     *
     * @return mixed The result of executing the next step.
     */
    public function executeNextStep(): mixed
    {
        $currentStep = $this->getNextStep();

        if ($currentStep === null) {
            return 'No next step.';
        }

        $result = $currentStep->execute();

        $this->currentStepDone();

        return $result;
    }

    /**
     * Determines the next step to be executed based on the previous step information.
     *
     * @return Step|null The next step to be executed, or null if there is no next step.
     */
    private function getNextStep(): ?Step
    {
        $prevStepInfo = $this->getLastStepInfo();

        // Check if there is no previous step information or if steps have changed
        if ($prevStepInfo === null || $prevStepInfo["step_hash"] !== md5(json_encode($this->steps))) {
            $this->currentStepIdx = 0;
            return $this->steps[0];
        }

        $this->currentStepIdx = (intval($prevStepInfo['last_step_index']) + 1) % count($this->steps);

        // Check if the delay for the current step has passed
        $delay = time() - ($prevStepInfo['timestamp'] + $this->steps[$this->currentStepIdx]->delay);
        if ($delay >= 0) {
            return $this->steps[$this->currentStepIdx];
        }

        return null;
    }

    /**
     * Records the completion of the current step.
     */
    private function currentStepDone(): void
    {
        $content = [
            'last_step_index' => $this->currentStepIdx,
            'timestamp' => time(),
            'step_hash' => md5(json_encode($this->steps))
        ];

        file_put_contents(self::STEP_FILE, json_encode($content));
    }

    /**
     * Gets information about the last executed step from the step file.
     *
     * @return array|null The information about the last executed step, or null if no information is available.
     */
    private function getLastStepInfo(): ?array
    {
        if (!file_exists(self::STEP_FILE) || filesize(self::STEP_FILE) === 0) {
            return null;
        }

        return json_decode(file_get_contents(self::STEP_FILE), true);
    }
}
