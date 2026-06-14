<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\CleanupResponseDraftsUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CleanupResponseDraftsAction
{
    public function __construct(
        private CleanupResponseDraftsUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $count = $this->useCase->execute();
        return JsonResponse::success($response, [
            'deleted_count' => $count,
            'message' => sprintf('%d 件の下書きをクリーンアップしました。', $count)
        ]);
    }
}
