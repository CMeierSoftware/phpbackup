<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Api;

if (!defined('ABS_PATH')) {
    return;
}

class AjaxController
{
    private readonly array $session;

    public function __construct(array &$session)
    {
        $this->session = &$session;
    }

    public function handleRequest()
    {
        $this->validateCSRFToken();

        try {
            $this->handleAjaxRequest();
        } catch (\Exception $e) {
            JsonResponse::sendError($e->getMessage(), 500);
        }
    }

    private function validateCSRFToken()
    {
        $token = isset($_SERVER['HTTP_CSRF_TOKEN']) ? $_SERVER['HTTP_CSRF_TOKEN'] : null;
        $token = $token ?? ($_POST['csrf_token'] ?? null);

        if (empty($token) || !hash_equals($this->session['CsrfToken'], $token)) {
            JsonResponse::sendError('Invalid or missing CSRF token.', 400);
        }
    }

    private function handleAjaxRequest()
    {
        // Handle different HTTP methods separately
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->handleGetRequest();
                break;
            case 'POST':
                $this->handlePostRequest();
                break;

            default:
                JsonResponse::sendError('Invalid HTTP method.', 405);
        }
    }

    private function handleGetRequest()
    {
        // Implement logic for handling AJAX GET requests
    }

    private function handlePostRequest()
    {
        $sanitizedData = $this->sanitizePostData($_POST);

        // Continue processing with sanitized data
        // ...

        // Send a success response
        JsonResponse::sendSuccess(['message' => 'Request processed successfully']);
    }

    private function sanitizePostData(array $postData): array
    {
        // Implement proper sanitization based on your requirements
        // For example, you might use filter_var or other sanitization functions
        $sanitizedData = [];

        foreach ($postData as $key => $value) {
            $sanitizedData[$key] = filter_var($value, FILTER_SANITIZE_STRING);
        }

        return $sanitizedData;
    }
}