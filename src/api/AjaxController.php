<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Api;

use CMS\PhpBackup\Core\AppConfig;

if (!defined('ABS_PATH')) {
    return;
}

class AjaxController
{
    public const CSRF_TOKEN_HEADER_NAME = 'HTTP_X_CSRF_TOKEN';

    public function __construct() {}

    public static function handleRequest()
    {
        self::validateReferer();
        self::validateCSRFToken();
        self::validateMethod();

        AppConfig::loadAppConfig(self::getApp());

        try {
            list($nonce, $action, $data) = self::sanitizePostData($_POST);

            $result = ActionHandler::getInstance()->exec($action, $nonce, $data);
        } catch (\Exception $e) {
            JsonResponse::sendError($e->getMessage(), 500);
        }
    }

    public static function printCsrfToken()
    {
        $csrfTokenName = htmlspecialchars(self::CSRF_TOKEN_HEADER_NAME, ENT_QUOTES, 'UTF-8');
        $csrfTokenContent = htmlspecialchars(self::getCsrfToken(), ENT_QUOTES, 'UTF-8');

        echo '<meta name="' . $csrfTokenName . '" content="' . $csrfTokenContent . '">';
    }

    private static function getCsrfToken()
    {
        if (empty($_SESSION[self::CSRF_TOKEN_HEADER_NAME])) {
            $_SESSION[self::CSRF_TOKEN_HEADER_NAME] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CSRF_TOKEN_HEADER_NAME];
    }

    private static function validateCSRFToken()
    {
        $token = $_SERVER[self::CSRF_TOKEN_HEADER_NAME] ?? '';

        if (empty($token) || !hash_equals(self::getCsrfToken(), $token)) {
            JsonResponse::sendError('Invalid or missing CSRF token.', 400);
        }
    }

    private static function validateReferer()
    {
        $httpRef = $_SERVER['HTTP_REFERER'] ?? null;
        $serverProtocol = isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'] ? 'https://' : 'http://';
        $address = $serverProtocol . $_SERVER['SERVER_NAME'] . '/';

        if (empty($httpRef) || !str_starts_with($httpRef, $address)) {
            JsonResponse::sendError('Invalid or missing referer header.', 400);
        }
    }

    private static function validateMethod()
    {
        if ('POST' !== $_SERVER['REQUEST_METHOD']) {
            JsonResponse::sendError('Invalid HTTP method.', 405);
        }
    }

    private static function getApp()
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            $refererParts = parse_url($_SERVER['HTTP_REFERER']);

            if (isset($refererParts['query'])) {
                parse_str($refererParts['query'], $queryParameters);

                if (isset($queryParameters['app'])) {
                    // You might want to perform additional validation or sanitization here
                    return trim($queryParameters['app']);
                }
            }
        }
        JsonResponse::sendError('Invalid URL parameter.', 403);
    }

    private static function sanitizePostData(array $postData): array
    {
        return [
            htmlspecialchars($postData['nonce'] ?? '', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($postData['action'] ?? '', ENT_QUOTES, 'UTF-8'),
            json_decode($postData['data'], true),
        ];
    }
}
