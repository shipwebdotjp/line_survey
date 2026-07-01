<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Respondent;

use App\Application\Admin\Respondent\ListRespondentsUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ListRespondentsAction
{
    public function __construct(
        private ListRespondentsUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $ownerUser = $request->getAttribute('owner_user');
        $respondents = $this->useCase->execute((int)$ownerUser['id']);
        return JsonResponse::success($response, $respondents);
    }
}
