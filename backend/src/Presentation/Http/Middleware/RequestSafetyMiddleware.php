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
        $appUrl = $this->settings->get('app.origin_url');
        if (!$appUrl) {
            error_log('RequestSafetyMiddleware: app.origin_url is not configured. Failing closed.');
            return false;
        }

        $expectedHost = parse_url($appUrl, PHP_URL_HOST);
        $expectedScheme = parse_url($appUrl, PHP_URL_SCHEME);
        $expectedPort = parse_url($appUrl, PHP_URL_PORT);
        if (!$expectedPort) {
            $expectedPort = ($expectedScheme === 'https') ? 443 : 80;
        }
        $expectedPort = (int)$expectedPort;

        $origin = $request->getHeaderLine('Origin');
        if (!empty($origin)) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            $originScheme = parse_url($origin, PHP_URL_SCHEME);
            $originPort = parse_url($origin, PHP_URL_PORT);
            if (!$originPort) {
                $originPort = ($originScheme === 'https') ? 443 : 80;
            }
            $originPort = (int)$originPort;

            return $originHost === $expectedHost &&
                   $originPort === $expectedPort &&
                   $originScheme === $expectedScheme;
        }

        // Fallback to Referer
        $referer = $request->getHeaderLine('Referer');
        if (!empty($referer)) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $refererScheme = parse_url($referer, PHP_URL_SCHEME);
            $refererPort = parse_url($referer, PHP_URL_PORT);
            if (!$refererPort) {
                $refererPort = ($refererScheme === 'https') ? 443 : 80;
            }
            $refererPort = (int)$refererPort;

            return $refererHost === $expectedHost &&
                   $refererPort === $expectedPort &&
                   $refererScheme === $expectedScheme;
        }

        return false;
    }

    private function isContentTypeSafe(ServerRequestInterface $request): bool
    {
        if ($this->isEmptyBody($request)) {
            return true;
        }

        $contentType = $request->getHeaderLine('Content-Type');
        if (empty($contentType)) {
            return false;
        }

        // Parse media type (strip parameters like ; charset=utf-8)
        $parts = explode(';', $contentType);
        $mediaType = strtolower(trim($parts[0]));

        return in_array($mediaType, ['application/json', 'multipart/form-data'], true);
    }

    private function isEmptyBody(ServerRequestInterface $request): bool
    {
        $contentLength = $request->getHeaderLine('Content-Length');
        if ($contentLength !== '') {
            return (int)$contentLength === 0;
        }

        $body = $request->getBody();
        $size = $body->getSize();
        if ($size !== null) {
            return $size === 0;
        }

        if ($body->isSeekable()) {
            $position = $body->tell();
            $body->rewind();
            $contents = $body->getContents();
            $body->seek($position);

            return $contents === '';
        }

        return false;
    }
}
