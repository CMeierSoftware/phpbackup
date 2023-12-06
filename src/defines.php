<?php

declare(strict_types=1);

if (!defined('ABS_PATH')) {
    define('ABS_PATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
}

if (!defined('CONFIG_DIR')) {
    define('CONFIG_DIR', ABS_PATH . 'config' . DIRECTORY_SEPARATOR);
}

require_once ABS_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
