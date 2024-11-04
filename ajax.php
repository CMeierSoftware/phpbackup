<?php

declare(strict_types=1);

namespace CMS\PhpBackup;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'defines.php';

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Api\AjaxController;

session_start();

// Handle the request
AjaxController::handleRequest();
