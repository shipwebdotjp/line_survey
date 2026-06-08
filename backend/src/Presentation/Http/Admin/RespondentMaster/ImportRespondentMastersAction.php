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
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;

        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return JsonResponse::error($response, 'VALIDATION_ERROR', 'Failed to upload file.', null, 400);
        }

        $csvContent = (string)$file->getStream();
        $result = $this->useCase->execute($csvContent);

        return JsonResponse::success($response, $result);
    }
}
