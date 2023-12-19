<?php
declare(strict_types=1);
use CMS\PhpBackup\App\BackupRunner;
use CMS\PhpBackup\Core\AppConfig;


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

$cfg = AppConfig::loadAppConfig($_GET['app']);

if (!$cfg) {
    $msg = 'Wrong App';
    header('HTTP/1.1 404 ' . $msg, true, 404);

    exit($msg);
}
$runner = new BackupRunner($cfg);

$runner->run();