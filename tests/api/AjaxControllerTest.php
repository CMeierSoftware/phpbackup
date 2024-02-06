<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Api;

use CMS\PhpBackup\Api\ActionHandler;
use CMS\PhpBackup\Api\AjaxController;
use CMS\PhpBackup\Helper\FileHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Api\AjaxController
 */
final class AjaxControllerTest extends TestCase
{
    protected const CONFIG_TEMP_DIR = CONFIG_DIR . 'temp_app' . DIRECTORY_SEPARATOR;
    protected const CONFIG_FILE = CONFIG_DIR . 'app.xml';

    protected function setUp(): void
    {
        parent::setUp();
        session_start();

        copy(TEST_FIXTURES_CONFIG_DIR . 'config_full_valid.xml', self::CONFIG_FILE);
        self::assertFileExists(self::CONFIG_FILE);
    }

    protected function tearDown(): void
    {
        session_destroy();

        FileHelper::deleteDirectory(self::CONFIG_TEMP_DIR);
        FileHelper::deleteFile(self::CONFIG_FILE);
        parent::tearDown();
    }

    /**
     * @covers \CMS\PhpBackup\Api\AjaxController::printCsrfToken()
     *
     * @uses \CMS\PhpBackup\Api\AjaxController::getCsrfToken()
     */
    public function testPrintCsrfTokenNewToken()
    {
        $expectedOutput = '/<meta name="HTTP_X_CSRF_TOKEN" content="[0-9a-f]{64}">/';

        ob_start();
        AjaxController::printCsrfToken();
        $first = ob_get_clean();
        ob_start();
        AjaxController::printCsrfToken();
        $second = ob_get_clean();

        self::assertMatchesRegularExpression($expectedOutput, $first);
        self::assertMatchesRegularExpression($expectedOutput, $second);
        self::assertSame($first, $second);
    }

    /**
     * @covers \CMS\PhpBackup\Api\AjaxController::printCsrfToken()
     *
     * @uses \CMS\PhpBackup\Api\AjaxController::getCsrfToken()
     */
    public function testPrintCsrfTokenExistingToken()
    {
        $token = 'random_string';
        $_SESSION['HTTP_X_CSRF_TOKEN'] = $token;
        $expectedOutput = "<meta name=\"HTTP_X_CSRF_TOKEN\" content=\"{$token}\">";

        ob_start();
        AjaxController::printCsrfToken();
        $output = ob_get_clean();

        self::assertSame($expectedOutput, $output);
    }

    /**
     * @covers \CMS\PhpBackup\Api\AjaxController::handleRequest()
     *
     * @uses \CMS\PhpBackup\Api\AjaxController::validateReferer()
     * @uses \CMS\PhpBackup\Api\AjaxController::validateCSRFToken()
     * @uses \CMS\PhpBackup\Api\AjaxController::validateMethod()
     * @uses \CMS\PhpBackup\Api\AjaxController::getSanitizedPostData()
     */
    public function testHandleRequest()
    {
        $actionHandlerMock = $this->getMockBuilder(ActionHandler::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        // Set up expectations
        $actionHandlerMock->expects(self::once())
            ->method('executeStep')
            ->willReturn(['success' => true, 'message' => 'Step executed successfully'])
        ;

        $reflection = new \ReflectionProperty(ActionHandler::class, 'instance');
        $reflection->setAccessible(true);
        $reflection->setValue($actionHandlerMock);

        $this->setServerVar();
        $_POST['nonce'] = 'foo';
        $_POST['action'] = 'bar';
        $_POST['data'] = json_encode(['seven']);

        ob_start();
        AjaxController::handleRequest();
        $output = ob_get_clean();
        var_dump($output);
    }

    /**
     * @dataProvider provideHandleRequestValidationCases
     *
     * @covers \CMS\PhpBackup\Api\AjaxController::handleRequest()
     *
     * @uses \CMS\PhpBackup\Api\AjaxController::validateReferer()
     * @uses \CMS\PhpBackup\Api\AjaxController::validateCSRFToken()
     * @uses \CMS\PhpBackup\Api\AjaxController::validateMethod()
     * @uses \CMS\PhpBackup\Api\AjaxController::getSanitizedPostData()
     */
    public function testHandleRequestValidation(string $key, ?string $value, string $msg, int $code, string $var)
    {
        $this->setServerVar($key, $value, $var);

        $expectedOutput = '{"error":"Invalid ' . $msg . '."}';
        ob_start();
        AjaxController::handleRequest();
        $output = ob_get_clean();
        self::assertSame($expectedOutput, $output, "Invalid Error message on {$var} key: {$key} value: {$value}");
        self::assertSame($code, http_response_code());
    }

    public static function provideHandleRequestValidationCases(): iterable
    {
        $vars = [
            ['HTTP_REFERER', 'or missing referer header', 400, 'SERVER'],
            ['HTTP_X_CSRF_TOKEN', 'or missing CSRF token', 400, 'SERVER'],
            ['REQUEST_METHOD', 'HTTP method', 405, 'SERVER'],
            ['nonce', 'request parameters', 400, 'POST'],
            ['action', 'request parameters', 400, 'POST'],
        ];

        $result = [];

        foreach ($vars as $item) {
            $result[] = [$item[0], null, $item[1], $item[2], $item[3]];
            $result[] = [$item[0], '', $item[1], $item[2], $item[3]];
        }

        // test case for missing app parameter
        $result[] = ['HTTP_REFERER', 'http://localhost/', 'URL parameter', 403, 'SERVER'];
        // test case for missing data parameter
        $result[] = ['data', null, 'request parameters', 400, 'POST'];
        $result[] = ['data', '{}', 'request parameters', 400, 'POST'];
        $result[] = ['data', '', 'data format', 400, 'POST']; // JSON error

        return $result;
    }

    private function setServerVar(string $key = '', ?string $value = '', string $var = '')
    {
        ob_start();
        AjaxController::printCsrfToken();
        $token = ob_get_clean();
        preg_match('/content="([0-9a-f]{64})"/', $token, $matches);
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $matches[1];
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['HTTP_REFERER'] = 'http://localhost/?app=app';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST['nonce'] = '';
        $_POST['action'] = '';
        $_POST['data'] = '{}';

        if ('POST' === $var) {
            if (null === $value) {
                unset($_POST[$key]);
            } else {
                $_POST[$key] = $value;
            }
        } elseif ('SERVER' === $var) {
            if (null === $value) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
    }
}
