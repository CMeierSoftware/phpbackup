<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Api;

if (!defined('ABS_PATH')) {
    return;
}

/**
 * JsonResponse class provides static methods for sending JSON-formatted responses.
 */
class JsonResponse
{
    /**
     * Sends a JSON error response with the specified message and HTTP status code.
     * Doesn't exit the script after sending the response.
     *
     * @param string $message the error message
     * @param int $code the HTTP status code for the response
     */
    public static function sendError(string $message, int $code)
    {
        self::sendResponse(['error' => $message], $code);
    }

    /**
     * Sends a JSON success response with the specified data and optional HTTP status code.
     * Doesn't exit the script after sending the response.
     *
     * @param array $data the data to include in the success response
     * @param int $code the optional HTTP status code for the response (default is 200)
     */
    public static function sendSuccess(array $data, int $code = 200)
    {
        self::sendResponse(['success' => true, 'data' => $data], $code);
    }

    private static function sendResponse(array $responseData, int $code)
    {
        $statusMessage = self::getStatusMessage($code);

        header("HTTP/1.1 {$code} {$statusMessage}", true, $code);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Security-Policy: default-src \'self\'');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');

        $jsonData = json_encode($responseData, JSON_UNESCAPED_UNICODE);

        if (false === $jsonData) {
            self::sendError('JSON encoding error', 500);
        }

        echo $jsonData;
    }

    private static function getStatusMessage(int $code): string
    {
        $statusMessages = [
            200 => 'OK',
            201 => 'Created',
            400 => 'Bad Request',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ];

        return $statusMessages[$code] ?? 'Unknown Status';
    }
}
