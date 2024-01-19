<?php
declare(strict_types=1);

namespace CMS\PhpBackup\Api;

/**
 * JsonResponse class provides static methods for sending JSON-formatted responses.
 *
 */
class JsonResponse
{
    /**
     * Sends a JSON error response with the specified message and HTTP status code.
     * Exits the script after sending the response.
     *
     * @param string $message The error message.
     * @param int $code The HTTP status code for the response.
     *
     * @return void
     */
    public static function sendError(string $message, int $code)
    {
        self::sendResponse(['error' => $message], $code);
    }

    /**
     * Sends a JSON success response with the specified data and optional HTTP status code.
     * Exits the script after sending the response.
     *
     * @param array $data The data to include in the success response.
     * @param int $code The optional HTTP status code for the response (default is 200).
     *
     * @return void
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

        if ($jsonData === false) {
            self::sendError('JSON encoding error', 500);
        }

        echo $jsonData;
        exit();
    }

    private static function getStatusMessage(int $code): string
    {
        $statusMessages = [
            200 => 'OK',
            201 => 'Created',
            // ... add more as needed
            400 => 'Bad Request',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        return $statusMessages[$code] ?? 'Unknown Status';
    }
}
