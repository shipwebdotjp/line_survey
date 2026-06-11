<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\RespondentMaster;

use App\Application\Admin\RespondentMaster\GetRespondentMasterUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetRespondentMasterAction
{
    public function __construct(
        private GetRespondentMasterUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $master = $this->useCase->execute($id);

        if (!$master) {
            return JsonResponse::error($response, 'NOT_FOUND', 'Respondent master not found', null, 404);
        }

        return JsonResponse::success($response, $master);
    }
}
