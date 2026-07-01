<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\ExportResponsesCsvUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ExportResponsesCsvAction
{
    public function __construct(
        private ExportResponsesCsvUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int)($args['id'] ?? 0);
        if ($id <= 0) {
            return \App\Presentation\Http\JsonResponse::error($response, 'VALIDATION_ERROR', 'Invalid ID', null, 400);
        }

        $ownerUser = $request->getAttribute('owner_user');
        $csv = $this->useCase->execute($id, (int)$ownerUser['id'], $request);

        $timestamp = \App\Infrastructure\Support\DateTimeHelper::nowTokyo()->format('YmdHis');
        $filename = sprintf('survey_%d_responses_%s.csv', $id, $timestamp);

        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $filename))
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');
    }
}
