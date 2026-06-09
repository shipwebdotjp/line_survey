<?php

declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class SessionMiddleware implements MiddlewareInterface
{
    private string $savePath;

    public function __construct()
    {
        // Path to backend/storage/sessions
        $baseDir = dirname(__DIR__, 4);
        $this->savePath = $baseDir . '/storage/sessions';

        if (!is_dir($this->savePath)) {
            if (!@mkdir($this->savePath, 0770, true) && !is_dir($this->savePath)) {
                throw new RuntimeException('Session save path does not exist and cannot be created: ' . $this->savePath);
            }
        }

        if (!is_writable($this->savePath)) {
            throw new RuntimeException('Session save path is not writable: ' . $this->savePath);
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = 60 * 60 * 24 * 14; // 14 days

            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            ini_set('session.gc_maxlifetime', (string)$lifetime);
            ini_set('session.save_path', $this->savePath);

            // Use __Host- prefix for better security.
            // Requires Secure=true, Path=/, and NO Domain attribute.
            session_name('__Host-survey_session');

            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            if (!session_start()) {
                throw new RuntimeException('Failed to start PHP session');
            }
        }

        return $handler->handle($request);
    }
}
