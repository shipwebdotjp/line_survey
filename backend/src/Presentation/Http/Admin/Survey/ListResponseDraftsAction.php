<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\ListResponseDraftsUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListResponseDraftsAction
{
    public function __construct(
        private ListResponseDraftsUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $drafts = $this->useCase->execute();
        return JsonResponse::success($response, ['drafts' => $drafts]);
    }
}
