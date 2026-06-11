<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\RespondentMaster;

use App\Application\Admin\RespondentMaster\DeleteRespondentMasterUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DeleteRespondentMasterAction
{
    public function __construct(
        private DeleteRespondentMasterUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $success = $this->useCase->execute($id);

        if (!$success) {
            return JsonResponse::error($response, 'NOT_FOUND', 'Respondent master not found', null, 404);
        }

        return JsonResponse::success($response, ['success' => true]);
    }
}
