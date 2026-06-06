<?php

namespace App\Presentation\Http;

use Psr\Http\Message\ResponseInterface as Response;

class JsonResponse
{
    public static function success(Response $response, mixed $data = null, int $statusCode = 200): Response
    {
        $payload = json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    public static function error(
        Response $response,
        string $code,
        string $message,
        mixed $details = null,
        int $statusCode = 400
    ): Response {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        $payload = json_encode(['error' => $error], JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
