<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Api;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Exceptions\AjaxValidationException;

if (!defined('ABS_PATH')) {
    return;
}

final class AjaxController
{
    public const CSRF_TOKEN_HEADER_NAME = 'HTTP_X_CSRF_TOKEN';

    public static function handleRequest()
    {
        try {
            self::validateReferer();
            self::validateCSRFToken();
            self::validateMethod();

            AppConfig::loadAppConfig(self::getApp());

            list($nonce, $step, $data) = self::getSanitizedPostData();

            if (empty($nonce) || empty($step) || empty($data)) {
                throw new \InvalidArgumentException('Invalid request parameters.', 400);
            }

            $data = ActionHandler::executeStep($step, $nonce, $data);
            JsonResponse::sendSuccess($data);
        } catch (\Exception $e) {
            JsonResponse::sendError($e->getMessage(), $e->getCode());
        }
    }

    public static function printCsrfToken()
    {
        $csrfTokenName = htmlspecialchars(self::CSRF_TOKEN_HEADER_NAME, ENT_QUOTES, 'UTF-8');
        $csrfTokenContent = htmlspecialchars(self::getCsrfToken(), ENT_QUOTES, 'UTF-8');

        echo '<meta name="' . $csrfTokenName . '" content="' . $csrfTokenContent . '">';
    }

    private static function getCsrfToken(): string
    {
        if (empty($_SESSION[self::CSRF_TOKEN_HEADER_NAME])) {
            $_SESSION[self::CSRF_TOKEN_HEADER_NAME] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CSRF_TOKEN_HEADER_NAME];
    }

    private static function validateCSRFToken(): void
    {
        $token = $_SERVER[self::CSRF_TOKEN_HEADER_NAME] ?? '';

        if (empty($token) || !hash_equals(self::getCsrfToken(), $token)) {
            throw new AjaxValidationException('Invalid or missing CSRF token.', 400);
        }
    }

    private static function validateReferer()
    {
        $httpRef = $_SERVER['HTTP_REFERER'] ?? null;
        $serverProtocol = isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'] ? 'https://' : 'http://';
        $address = $serverProtocol . $_SERVER['SERVER_NAME'] . '/';

        if (empty($httpRef) || !str_starts_with($httpRef, $address)) {
            throw new AjaxValidationException('Invalid or missing referer header.', 400);
        }
    }

    private static function validateMethod()
    {
        if ('POST' !== $_SERVER['REQUEST_METHOD']) {
            throw new AjaxValidationException('Invalid HTTP method.', 405);
        }
    }

    private static function getApp(): string
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

        throw new AjaxValidationException('Invalid URL parameter.', 403);
    }

    private static function getSanitizedPostData(): array
    {
        // Sanitize and validate the POST data
        $nonce = htmlspecialchars($_POST['nonce'] ?? '', ENT_QUOTES, 'UTF-8');
        $step = htmlspecialchars($_POST['action'] ?? '', ENT_QUOTES, 'UTF-8');
        $data = json_decode($_POST['data'] ?? '{}', true);

        // Check if data is an array
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid data format.', 400);
        }

        return [$nonce, $step, $data];
    }
}
