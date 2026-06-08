<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\ListResponsesUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ListResponsesAction
{
    public function __construct(
        private ListResponsesUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        if ($id <= 0) {
            return JsonResponse::error($response, 'Invalid ID', 'VALIDATION_ERROR', 400);
        }

        $data = $this->useCase->execute($id, $request);

        return JsonResponse::success($response, $data);
    }
}
