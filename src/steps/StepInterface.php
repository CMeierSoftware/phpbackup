<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Step;

if (!defined('ABS_PATH')) {
    return;
}

interface StepInterface
{
    public function execute(): StepResult;

    /**
     * Get the keys required in the data array.
     *
     * @return array list of required data keys
     */
    public function getRequiredDataKeys(): array;
}
