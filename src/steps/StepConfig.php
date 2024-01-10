<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

use CMS\PhpBackup\Core\AppConfig;

if (!defined('ABS_PATH')) {
    return;
}

final class StepConfig
{
    private readonly string $stepClass;
    private readonly int $delay;

    public function __construct(string $stepClass, int $delay = 0)
    {
        if (!class_exists($stepClass) || !is_subclass_of($stepClass, AbstractStep::class)) {
            throw new \UnexpectedValueException('All entries in the array must be classes extended AbstractStep.');
        }

        if ($delay < 0) {
            throw new \UnexpectedValueException('Delay must be integer greater or equal than 0.');
        }

        $this->stepClass = $stepClass;
        $this->delay = $delay;
    }

    public function getStepObject(AppConfig $appConfig)
    {
        return new $this->stepClass($appConfig, $this->delay);
    }
}
