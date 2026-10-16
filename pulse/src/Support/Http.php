<?php

declare(strict_types=1);

namespace Pulse\Support;

final class Http
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function sessionKey(): string
    {
        self::startSession();

        if (!isset($_SESSION['visitor_key'])) {
            $_SESSION['visitor_key'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['visitor_key'];
    }

    public static function csrfToken(): string
    {
        self::startSession();

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
        }

        return (string) $_SESSION['csrf_token'];
    }

    public static function requireCsrf(): void
    {
        $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $token = self::csrfToken();

        if (!is_string($provided) || $provided === '' || !hash_equals($token, $provided)) {
            throw new ApiException(419, 'CSRF_INVALID', '页面令牌已失效，请刷新后再试。');
        }
    }

    public static function input(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (!str_contains($contentType, 'application/json')) {
            return $_POST;
        }

        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function success(array $data = [], int $status = 200): never
    {
        self::json([
            'ok' => true,
            'data' => $data,
        ], $status);
    }

    public static function error(string $code, string $message, int $status = 400): never
    {
        self::json([
            'ok' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }

    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

