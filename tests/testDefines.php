<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . '..\\defines.php';

if (!defined('TEST_WORK_DIR')) {
    define('TEST_WORK_DIR', ABS_PATH . 'tests' . DIRECTORY_SEPARATOR . 'work' . DIRECTORY_SEPARATOR);
}

if (!defined('TEST_FIXTURES_DIR')) {
    define('TEST_FIXTURES_DIR', ABS_PATH . 'tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR);
}
if (!defined('TEST_FIXTURES_FILE_DIR')) {
    define('TEST_FIXTURES_FILE_DIR', TEST_FIXTURES_DIR . 'files' . DIRECTORY_SEPARATOR);
}
if (!defined('TEST_FIXTURES_CONFIG_DIR')) {
    define('TEST_FIXTURES_CONFIG_DIR', TEST_FIXTURES_DIR . 'config' . DIRECTORY_SEPARATOR);
}
if (!defined('TEST_FIXTURES_ENCRYPTION_DIR')) {
    define('TEST_FIXTURES_ENCRYPTION_DIR', TEST_FIXTURES_DIR . 'encryption' . DIRECTORY_SEPARATOR);
}
if (!defined('TEST_FIXTURES_STEPS_DIR')) {
    define('TEST_FIXTURES_STEPS_DIR', TEST_FIXTURES_DIR . 'steps' . DIRECTORY_SEPARATOR);
}

use PHPUnit\Framework\Assert;

if (!defined('TEST_FIXTURES_FILE_1')) {
    define('TEST_FIXTURES_FILE_1', TEST_FIXTURES_FILE_DIR . 'file1.txt');
    Assert::assertFileExists(TEST_FIXTURES_FILE_1);
}
if (!defined('TEST_FIXTURES_FILE_2')) {
    define('TEST_FIXTURES_FILE_2', TEST_FIXTURES_FILE_DIR . 'file2.xls');
    Assert::assertFileExists(TEST_FIXTURES_FILE_2);
}
if (!defined('TEST_FIXTURES_FILE_3')) {
    define('TEST_FIXTURES_FILE_3', TEST_FIXTURES_FILE_DIR . 'file3.txt');
    Assert::assertFileExists(TEST_FIXTURES_FILE_3);
}
