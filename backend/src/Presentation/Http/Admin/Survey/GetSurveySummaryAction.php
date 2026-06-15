<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\GetSurveySummaryUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

final class GetSurveySummaryAction
{
    public function __construct(
        private GetSurveySummaryUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        try {
            $summary = $this->useCase->execute($id, $request);
            return JsonResponse::success($response, $summary);
        } catch (HttpNotFoundException $e) {
            return JsonResponse::error($response, 'NOT_FOUND', $e->getMessage(), null, 404);
        } catch (\Throwable $e) {
            error_log((string)$e);
            return JsonResponse::error($response, 'INTERNAL_ERROR', 'An internal error occurred', null, 500);
        }
    }
}
