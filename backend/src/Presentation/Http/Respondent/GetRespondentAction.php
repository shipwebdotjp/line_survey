<?php

declare(strict_types=1);

namespace App\Presentation\Http\Respondent;

use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetRespondentAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array $respondent */
        $respondent = $request->getAttribute('respondent');
        if (!is_array($respondent)) {
            // AuthSessionMiddleware should have attached an array if successful
            throw new \RuntimeException('Respondent attribute must be an array. Ensure AuthSessionMiddleware is active.');
        }

        return JsonResponse::success($response, [
            'id' => $respondent['id'],
            'name' => $respondent['name'],
            'email' => $respondent['email'],
            'line_display_name' => $respondent['line_display_name'],
        ]);
    }
}
