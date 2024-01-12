<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

@ini_set("memory_limit", "512M");
set_time_limit ( 1200 );

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

require_once ABS_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Set an environment variable
putenv('MYSQLDUMP_EXE=C:\\xampp\\mysql\\bin\\mysqldump');
