<?php

use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\Liff\IdentifyAction;
use App\Presentation\Http\Liff\IdentifyManualAction;
use App\Presentation\Http\Survey\GetPublicSurveyAction;
use App\Presentation\Http\Survey\SaveResponseAction;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    $app->get('/api/health', function (Request $request, Response $response) {
        return JsonResponse::success($response, ['status' => 'ok']);
    });

    // LIFF Identification
    $app->post('/api/liff/identify', IdentifyAction::class);
    $app->post('/api/liff/identify/manual', IdentifyManualAction::class);

    // Public Survey
    $app->get('/api/surveys/public/{public_id}', GetPublicSurveyAction::class);
    $app->post('/api/surveys/public/{public_id}/responses', SaveResponseAction::class);
};
