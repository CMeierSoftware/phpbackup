<?php

declare(strict_types=1);

namespace CMS\PhpBackup;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

@ini_set('memory_limit', '512M');
set_time_limit(1200);

if (!defined('CMS_DEBUG')) {
    define('CMS_DEBUG', true);
}

if (!defined('ABS_PATH')) {
    define('ABS_PATH', realpath(__DIR__) . DIRECTORY_SEPARATOR);
}

if (!defined('CONFIG_DIR')) {
    define('CONFIG_DIR', ABS_PATH . 'config' . DIRECTORY_SEPARATOR);
}
if (!file_exists(CONFIG_DIR)) {
    mkdir(CONFIG_DIR, 0o755, true);
}

if (!defined('TEMP_DIR')) {
    define('TEMP_DIR', ABS_PATH . 'temp' . DIRECTORY_SEPARATOR);
}
if (!file_exists(TEMP_DIR)) {
    mkdir(TEMP_DIR, 0o755, true);
}

function CMS_is_debug_mode()
{
    return defined('CMS_DEBUG') && CMS_DEBUG;
}

require_once ABS_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use CMS\PhpBackup\Helper\FileLogger;
use CMS\PhpBackup\Helper\LogLevel;

if (CMS_is_debug_mode()) {
    $logger = FileLogger::getInstance();
    $logger->activateEchoLogs();
    $logger->setLogLevel(LogLevel::DEBUG);

    $logger->debug('ABS_PATH: ' . ABS_PATH);
    $logger->debug('CONFIG_DIR: ' . CONFIG_DIR);
    $logger->debug('TEMP_DIR: ' . TEMP_DIR);
}

// Set an environment variable
putenv('MYSQLDUMP_EXE=C:\\xampp\\mysql\\bin\\mysqldump');
