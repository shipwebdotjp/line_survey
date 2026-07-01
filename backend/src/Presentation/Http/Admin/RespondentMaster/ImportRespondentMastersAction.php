<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\RespondentMaster;

use App\Application\Admin\RespondentMaster\ImportRespondentMastersUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ImportRespondentMastersAction
{
    public function __construct(
        private ImportRespondentMastersUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $ownerUser = $request->getAttribute('owner_user');
        if (!is_array($ownerUser) || !isset($ownerUser['id']) || (int)$ownerUser['id'] <= 0) {
            return JsonResponse::error($response, 'OWNER_SESSION_REQUIRED', 'Admin session is required', null, 401);
        }

        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;

        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Failed to upload file.', null, 400);
        }

        $csvContent = (string)$file->getStream();
        $result = $this->useCase->execute($csvContent, (int)$ownerUser['id']);

        return JsonResponse::success($response, $result);
    }
}
