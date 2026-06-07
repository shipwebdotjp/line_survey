<?php

namespace App\Presentation\Http\Middleware;

use App\Config\Settings;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class BasicAuthMiddleware implements MiddlewareInterface
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $auth = $request->getHeaderLine('Authorization');

        if ($auth === '' || !preg_match('/^Basic\s+/i', $auth)) {
            return $this->unauthorized();
        }

        $credentials = base64_decode(substr($auth, 6), true);
        if ($credentials === false || !str_contains($credentials, ':')) {
            return $this->unauthorized();
        }

        [$user, $pass] = explode(':', $credentials, 2);

        $adminUser = $this->settings->get('auth.admin_user');
        $adminPass = $this->settings->get('auth.admin_pass');

        if ($user !== $adminUser || $pass !== $adminPass) {
            return $this->unauthorized();
        }

        return $handler->handle($request);
    }

    private function unauthorized(): Response
    {
        $response = new SlimResponse();
        return JsonResponse::error(
            $response,
            'UNAUTHORIZED',
            'Authentication required.',
            null,
            401
        )->withHeader('WWW-Authenticate', 'Basic realm="Admin"');
    }
}
