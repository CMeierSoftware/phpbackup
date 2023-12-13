<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Exceptions;

if (!defined('ABS_PATH')) {
    return;
}

final class RemoteStorageNotConnectedException extends \Exception {}
