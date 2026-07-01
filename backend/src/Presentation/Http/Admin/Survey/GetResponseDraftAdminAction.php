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
        $idStr = $args['id'] ?? '';
        if ($idStr === '' || !is_numeric($idStr) || (int)$idStr <= 0) {
            return JsonResponse::error($response, 'INVALID_ID', '有効なIDを指定してください。', null, 400);
        }

        $id = (int)$idStr;
        $ownerUser = $request->getAttribute('owner_user');
        $draft = $this->useCase->execute($id, (int)$ownerUser['id']);

        if (!$draft) {
            return JsonResponse::error($response, 'NOT_FOUND', '下書きが見つかりませんでした。', null, 404);
        }

        return JsonResponse::success($response, ['draft' => $draft]);
    }
}
