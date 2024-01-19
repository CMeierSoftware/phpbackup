<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'defines.php';

if (!defined('ABS_PATH')) {
    return;
}

use CMS\PhpBackup\Api\AjaxController;

session_start();

$session = &$_SESSION;
$ajaxController = new AjaxController($session);

// Handle the request
$ajaxController->handleRequest();
