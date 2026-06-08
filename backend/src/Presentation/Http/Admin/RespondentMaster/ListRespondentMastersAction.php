<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\RespondentMaster;

use App\Application\Admin\RespondentMaster\ListRespondentMastersUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListRespondentMastersAction
{
    public function __construct(
        private ListRespondentMastersUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $masters = $this->useCase->execute();
        return JsonResponse::success($response, $masters);
    }
}
