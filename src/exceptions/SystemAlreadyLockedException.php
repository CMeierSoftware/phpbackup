<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Exceptions;

if (!defined('ABS_PATH')) {
    return;
}

/**
 * Exception is thrown when a system is already locked.
 */
final class SystemAlreadyLockedException extends \Exception
{
}
