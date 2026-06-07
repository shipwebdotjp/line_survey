<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\ListSurveysUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListSurveysAction
{
    public function __construct(
        private ListSurveysUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $surveys = $this->useCase->execute();
        return JsonResponse::success($response, $surveys);
    }
}
