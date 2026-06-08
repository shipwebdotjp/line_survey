<?php

use App\Presentation\Http\Admin\Survey\CreateSurveyAction;
use App\Presentation\Http\Admin\Survey\DeleteSurveyAction;
use App\Presentation\Http\Admin\Survey\ExportResponsesCsvAction;
use App\Presentation\Http\Admin\Survey\GetResponseAction;
use App\Presentation\Http\Admin\Survey\GetSurveyAction;
use App\Presentation\Http\Admin\Survey\ListResponsesAction;
use App\Presentation\Http\Admin\RespondentMaster\ImportRespondentMastersAction;
use App\Presentation\Http\Admin\RespondentMaster\ListRespondentMastersAction;
use App\Presentation\Http\Admin\Survey\ListSurveysAction;
use App\Presentation\Http\Admin\Survey\UpdateSurveyAction;
use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\Liff\IdentifyAction;
use App\Presentation\Http\Liff\IdentifyManualAction;
use App\Presentation\Http\Middleware\BasicAuthMiddleware;
use App\Presentation\Http\Survey\GetPublicSurveyAction;
use App\Presentation\Http\Survey\SaveResponseAction;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

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

    // Admin API (Basic Auth protected)
    $app->group('/api/admin', function (RouteCollectorProxy $group) {
        $group->get('/surveys', ListSurveysAction::class);
        $group->post('/surveys', CreateSurveyAction::class);
        $group->get('/surveys/{id:[0-9]+}', GetSurveyAction::class);
        $group->put('/surveys/{id:[0-9]+}', UpdateSurveyAction::class);
        $group->delete('/surveys/{id:[0-9]+}', DeleteSurveyAction::class);
        $group->get('/surveys/{id:[0-9]+}/responses', ListResponsesAction::class);
        $group->get('/surveys/{id:[0-9]+}/responses/{responseId:[0-9]+}', GetResponseAction::class);
        $group->get('/surveys/{id:[0-9]+}/responses.csv', ExportResponsesCsvAction::class);

        // Respondent Masters
        $group->get('/respondent-masters', ListRespondentMastersAction::class);
        $group->post('/respondent-masters/import', ImportRespondentMastersAction::class);
    })->add(BasicAuthMiddleware::class);
};
