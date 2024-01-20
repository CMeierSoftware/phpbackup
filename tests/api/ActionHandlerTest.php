<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\App;

use CMS\PhpBackup\Api\ActionHandler;
use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Api\ActionHandler
 */
final class ActionHandlerTest extends TestCase
{
    private const TEST_CONFIG_TEMP_DIR = CONFIG_DIR . 'temp_config_full_valid' . DIRECTORY_SEPARATOR;
    private const ACTION_FILE = self::TEST_CONFIG_TEMP_DIR . 'actions.json';
    private const TEST_CONFIG_FILE = TEST_FIXTURES_CONFIG_DIR . 'config_full_valid.xml';

    private AppConfig $config;
    private ActionHandler $handler;

    protected function setUp(): void
    {
        FileHelper::makeDir(CONFIG_DIR);
        copy(TEST_FIXTURES_CONFIG_DIR . 'config_full_valid.xml', CONFIG_DIR . 'config_full_valid.xml');
        self::assertFileExists(CONFIG_DIR . 'config_full_valid.xml');

        $this->config = AppConfig::loadAppConfig('config_full_valid');
        $this->handler = ActionHandler::getInstance($this->config);
    }

    protected function tearDown(): void
    {
        FileHelper::deleteDirectory(self::TEST_CONFIG_TEMP_DIR);
        FileHelper::deleteDirectory(TEMP_DIR);
        FileHelper::deleteDirectory(CONFIG_DIR);
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::getInstance()
     */
    public function testSingleton()
    {
        $obj1 = ActionHandler::getInstance($this->config);
        $obj2 = ActionHandler::getInstance($this->config);

        self::assertSame($obj1, $obj2);
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::registerAction()
     */
    public function testRegisterAction()
    {
        $expectedJson = json_encode(['getHelloWorld' => [ActionStub::class, 'printHelloWorld']]);

        $this->handler->registerAction('getHelloWorld', [ActionStub::class, 'printHelloWorld']);

        self::assertFileExists(self::ACTION_FILE);
        self::assertJsonStringEqualsJsonFile(self::ACTION_FILE, $expectedJson);
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::registerAction()
     */
    public function testRegisterActionOverwrite()
    {
        $expectedJson = json_encode(['getHelloWorld' => [ActionStub::class, 'printFoo']]);

        $this->handler->registerAction('getHelloWorld', [ActionStub::class, 'printHelloWorld']);
        $this->handler->registerAction('getHelloWorld', [ActionStub::class, 'printFoo']);

        self::assertFileExists(self::ACTION_FILE);
        self::assertJsonStringEqualsJsonFile(self::ACTION_FILE, $expectedJson);
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::unregisterAction()
     *
     * @uses \CMS\PhpBackup\Api\ActionHandler::isActionRegistered()
     */
    public function testUnregisterAction()
    {
        $this->handler->registerAction('getHelloWorld', [ActionStub::class, 'printHelloWorld']);

        $this->handler->unregisterAction('getHelloWorld');

        self::assertFileExists(self::ACTION_FILE);
        self::assertJsonStringEqualsJsonFile(self::ACTION_FILE, json_encode([]));
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::unregisterAction()
     *
     * @uses \CMS\PhpBackup\Api\ActionHandler::isActionRegistered()
     */
    public function testUnregisterActionNotFound()
    {
        $expectedJson = json_encode(['getHelloWorld' => [ActionStub::class, 'printHelloWorld']]);

        $this->handler->registerAction('getHelloWorld', [ActionStub::class, 'printHelloWorld']);

        $this->handler->unregisterAction('NotFound');

        self::assertFileExists(self::ACTION_FILE);
        self::assertJsonStringEqualsJsonFile(self::ACTION_FILE, $expectedJson);
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::generateNonce()
     */
    public function testGenerateNonce()
    {
        $nonce = $this->handler->generateNonce('getHelloWorld');
        $nonce2 = $this->handler->generateNonce('getHelloWorld');
        $nonce3 = $this->handler->generateNonce('somethingElse');

        self::assertIsString($nonce);
        self::assertSame(32, strlen($nonce));
        self::assertSame($nonce, $nonce2);
        self::assertNotSame($nonce, $nonce3);
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::executeAction()
     *
     * @uses \CMS\PhpBackup\Api\ActionHandler::isActionRegistered()
     * @uses \CMS\PhpBackup\Api\ActionHandler::validateNonce()
     */
    public function testExecuteAction()
    {
        $nonce = $this->handler->generateNonce('getHelloWorld');

        $this->handler->registerAction('getHelloWorld', [ActionStub::class, 'printHelloWorld']);

        ob_start();
        $this->handler->executeAction('getHelloWorld', $nonce);
        $result = ob_get_clean();

        self::assertSame('Hello World', $result);
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::executeAction()
     *
     * @uses \CMS\PhpBackup\Api\ActionHandler::isActionRegistered()
     * @uses \CMS\PhpBackup\Api\ActionHandler::validateNonce()
     */
    public function testExecuteActionWithOneParameter()
    {
        $nonce = $this->handler->generateNonce('getHelloWorld');

        $params = [
            'message' => 'Bar',
        ];

        $this->handler->registerAction('getHelloWorld', [ActionStub::class, 'printFoo']);

        ob_start();
        $this->handler->executeAction('getHelloWorld', $nonce, $params);
        $result = ob_get_clean();

        self::assertSame('Foo Bar', $result);
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::executeAction()
     *
     * @uses \CMS\PhpBackup\Api\ActionHandler::isActionRegistered()
     * @uses \CMS\PhpBackup\Api\ActionHandler::validateNonce()
     */
    public function testExecuteActionWithMultipleParameter()
    {
        $nonce = $this->handler->generateNonce('getHelloWorld');

        $params = [
            'message' => 'Bar',
            'data' => ['orange', 'banana', 'apple'],
        ];

        $this->handler->registerAction('getHelloWorld', [ActionStub::class, 'printArray']);

        ob_start();
        $this->handler->executeAction('getHelloWorld', $nonce, $params);
        $result = ob_get_clean();

        self::assertSame($params['message'] . serialize($params['data']), $result);
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::executeAction()
     *
     * @uses \CMS\PhpBackup\Api\ActionHandler::isActionRegistered()
     * @uses \CMS\PhpBackup\Api\ActionHandler::validateNonce()
     */
    public function testExecuteActionNotRegistered()
    {
        $nonce = $this->handler->generateNonce('getHelloWorld');

        $this->handler->registerAction('getHelloWorld', [ActionStub::class, 'printHelloWorld']);

        self::expectException(\InvalidArgumentException::class);
        $this->handler->executeAction('somethingElse', $nonce);
    }

    /**
     * @covers \CMS\PhpBackup\Api\ActionHandler::executeAction()
     *
     * @uses \CMS\PhpBackup\Api\ActionHandler::isActionRegistered()
     * @uses \CMS\PhpBackup\Api\ActionHandler::validateNonce()
     */
    public function testExecuteActionInvalidNonce()
    {
        $this->handler->registerAction('getHelloWorld', [ActionStub::class, 'printHelloWorld']);

        self::expectException(\RuntimeException::class);
        $this->handler->executeAction('getHelloWorld', '');
    }
}

final class ActionStub
{
    public static function printHelloWorld()
    {
        echo 'Hello World';
    }

    public static function printFoo(string $message)
    {
        echo 'Foo ' . $message;
    }

    public static function printArray(string $message, array $data)
    {
        echo $message . serialize($data);
    }
}
