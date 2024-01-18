<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['key']) || 'pZk5ukBLtZ6wdQgZ' !== $_GET['key']) {
    $msg = 'Forbidden';
    header('HTTP/1.1 403 ' . $msg, true, 403);

    exit($msg);
}

if (!isset($_GET['app']) || empty($_GET['app'])) {
    $msg = 'Forbidden';
    header('HTTP/1.1 403 ' . $msg, true, 403);

    exit($msg);
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'defines.php';

if (!defined('ABS_PATH')) {
    $msg = 'ABS_PATH not defined';
    header('HTTP/1.1 500 ' . $msg, true, 500);

    exit($msg);
}

use CMS\PhpBackup\App\BackupRunner;
use CMS\PhpBackup\Core\AppConfig;

$apps = explode(',', $_GET['app']);

foreach ($apps as $appName) {
    $cfg = AppConfig::loadAppConfig($appName);

    if (!$cfg) {
        $msg = "Wrong App: {$appName}";
        header('HTTP/1.1 404 ' . $msg, true, 404);

        exit($msg);
    }

    $runner = new BackupRunner($cfg);

    if ($runner->run()) {
        break;
    }
}

echo '<h2>Step Execution completed.</h2>';
