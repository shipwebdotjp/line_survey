<?php

declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Config\Settings;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

final class RequestSafetyMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Settings $settings,
        private ResponseFactory $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $unsafeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        if (in_array($method, $unsafeMethods, true)) {
            // 1. Origin / Referer Check
            if (!$this->isOriginSafe($request)) {
                $response = $this->responseFactory->createResponse(403);
                return JsonResponse::error($response, 'INVALID_ORIGIN', 'Forbidden', null, 403);
            }

            // 2. Content-Type Check
            if (!$this->isContentTypeSafe($request)) {
                $response = $this->responseFactory->createResponse(415);
                return JsonResponse::error($response, 'UNSUPPORTED_CONTENT_TYPE', 'Unsupported Media Type', null, 415);
            }
        }

        return $handler->handle($request);
    }

    private function isOriginSafe(ServerRequestInterface $request): bool
    {
        $appUrl = $this->settings->get('app.url');
        if (!$appUrl) {
            return true; // If not configured, we can't check
        }

        $expectedHost = parse_url($appUrl, PHP_URL_HOST);
        $expectedPort = parse_url($appUrl, PHP_URL_PORT);
        $expectedScheme = parse_url($appUrl, PHP_URL_SCHEME);

        $origin = $request->getHeaderLine('Origin');
        if (!empty($origin)) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            $originPort = parse_url($origin, PHP_URL_PORT);
            $originScheme = parse_url($origin, PHP_URL_SCHEME);

            return $originHost === $expectedHost &&
                   $originPort === $expectedPort &&
                   $originScheme === $expectedScheme;
        }

        // Fallback to Referer
        $referer = $request->getHeaderLine('Referer');
        if (!empty($referer)) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $refererPort = parse_url($referer, PHP_URL_PORT);
            $refererScheme = parse_url($referer, PHP_URL_SCHEME);

            return $refererHost === $expectedHost &&
                   $refererPort === $expectedPort &&
                   $refererScheme === $expectedScheme;
        }

        return false;
    }

    private function isContentTypeSafe(ServerRequestInterface $request): bool
    {
        $contentType = $request->getHeaderLine('Content-Type');

        // We only allow application/json
        return str_starts_with(strtolower($contentType), 'application/json');
    }
}
