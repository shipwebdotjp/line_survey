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
        $id = (int)$args['id'];
        $csv = $this->useCase->execute($id, $request);

        $filename = sprintf('survey_%d_responses_%s.csv', $id, date('YmdHis'));

        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $filename))
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');
    }
}
