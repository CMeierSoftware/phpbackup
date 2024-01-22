<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\App;

use CMS\PhpBackup\Api\JsonResponse;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class JsonResponseTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testSendError()
    {
        ob_start();
        JsonResponse::sendError('Test Error', 404);
        $output = ob_get_clean();

        self::assertStringContainsString('"error":"Test Error"', $output);
        self::assertStringContainsString('HTTP/1.1 404 Not Found', $output);
        // Add more assertions as needed
    }

    public function testSendSuccess()
    {
        ob_start();
        JsonResponse::sendSuccess(['data' => 'Test Data']);
        $output = ob_get_clean();

        self::assertStringContainsString('"success":true', $output);
        self::assertStringContainsString('"data":{"data":"Test Data"}', $output);
        // Add more assertions as needed
    }
}
