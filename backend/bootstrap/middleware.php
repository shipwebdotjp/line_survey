<?php

use Slim\App;
use Slim\Exception\HttpSpecializedException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Handlers\ErrorHandler;

return function (App $app) {
    // Parse json, form data and xml
    $app->addBodyParsingMiddleware();

    // Add Routing Middleware
    $app->addRoutingMiddleware();

    // Add Error Middleware
    $settings = $app->getContainer()->get(\App\Config\Settings::class);
    $errorMiddleware = $app->addErrorMiddleware(
        $settings->get('error.display_error_details'),
        $settings->get('error.log_errors'),
        $settings->get('error.log_error_details')
    );

    // Custom Error Handler
    $errorHandler = function (
        Request $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app) {
        $statusCode = 500;
        $errorCode = 'INTERNAL_ERROR';
        $message = 'An internal server error occurred.';
        $details = null;

        if ($exception instanceof HttpSpecializedException) {
            $statusCode = $exception->getCode();
            $message = $exception->getMessage();

            if ($statusCode === 404) {
                $errorCode = 'NOT_FOUND';
            } elseif ($statusCode === 405) {
                $errorCode = 'METHOD_NOT_ALLOWED';
            } elseif ($statusCode === 401) {
                $errorCode = 'UNAUTHORIZED';
            } elseif ($statusCode === 403) {
                $errorCode = 'FORBIDDEN';
            }
        }

        // Handle specific custom exceptions if needed in the future

        $payload = [
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
        ];

        if ($details !== null) {
            $payload['error']['details'] = $details;
        }

        if ($displayErrorDetails) {
            $payload['error']['debug'] = [
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        $response = $app->getResponseFactory()->createResponse($statusCode);
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response->withHeader('Content-Type', 'application/json');
    };

    $errorMiddleware->setDefaultErrorHandler($errorHandler);
};
