<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Tests\Api;

use CMS\PhpBackup\Api\JsonResponse;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \CMS\PhpBackup\Api\JsonResponse
 */
final class JsonResponseTest extends TestCase
{
    /**
     * @covers \CMS\PhpBackup\Api\JsonResponse::testSendError()
     */
    public function testSendError()
    {
        ob_start();
        JsonResponse::sendError('Test Error', 404);
        $output = ob_get_clean();

        self::assertStringContainsString('"error":"Test Error"', $output);
        self::assertSame(404, http_response_code());
    }

    /**
     * @covers \CMS\PhpBackup\Api\JsonResponse::testSendSuccess()
     */
    public function testSendSuccess()
    {
        ob_start();
        JsonResponse::sendSuccess(['data' => 'Test Data']);
        $output = ob_get_clean();

        self::assertStringContainsString('"success":true', $output);
        self::assertStringContainsString('"data":{"data":"Test Data"}', $output);
    }
}
