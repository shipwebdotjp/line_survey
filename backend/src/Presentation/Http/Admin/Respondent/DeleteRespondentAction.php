<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Respondent;

use App\Application\Admin\Respondent\DeleteRespondentUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DeleteRespondentAction
{
    public function __construct(
        private DeleteRespondentUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $success = $this->useCase->execute($id);

        if (!$success) {
            return JsonResponse::error($response, 'NOT_FOUND', 'Respondent not found', null, 404);
        }

        return JsonResponse::success($response, ['success' => true]);
    }
}
