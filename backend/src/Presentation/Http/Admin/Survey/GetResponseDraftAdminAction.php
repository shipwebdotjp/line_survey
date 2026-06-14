<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\GetResponseDraftAdminUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetResponseDraftAdminAction
{
    public function __construct(
        private GetResponseDraftAdminUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $draft = $this->useCase->execute($id);

        if (!$draft) {
            return JsonResponse::error($response, '下書きが見つかりませんでした。', 404);
        }

        return JsonResponse::success($response, ['draft' => $draft]);
    }
}
