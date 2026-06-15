<?php

use App\Presentation\Http\Admin\Survey\CreateSurveyAction;
use App\Presentation\Http\Admin\Survey\DuplicateSurveyAction;
use App\Presentation\Http\Admin\Survey\DeleteSurveyAction;
use App\Presentation\Http\Admin\Survey\DeleteResponseAction;
use App\Presentation\Http\Admin\Survey\ExportResponsesCsvAction;
use App\Presentation\Http\Admin\Survey\GetResponseAction;
use App\Presentation\Http\Admin\Survey\GetSurveyAction;
use App\Presentation\Http\Admin\Survey\GetSurveySummaryAction;
use App\Presentation\Http\Admin\Survey\ListResponsesAction;
use App\Presentation\Http\Admin\Survey\ListResponseDraftsAction;
use App\Presentation\Http\Admin\Survey\GetResponseDraftAdminAction;
use App\Presentation\Http\Admin\Survey\CleanupResponseDraftsAction;
use App\Presentation\Http\Admin\RespondentMaster\CreateRespondentMasterAction;
use App\Presentation\Http\Admin\RespondentMaster\DeleteRespondentMasterAction;
use App\Presentation\Http\Admin\RespondentMaster\GetRespondentMasterAction;
use App\Presentation\Http\Admin\RespondentMaster\ImportRespondentMastersAction;
use App\Presentation\Http\Admin\RespondentMaster\ListRespondentMastersAction;
use App\Presentation\Http\Admin\RespondentMaster\UpdateRespondentMasterAction;
use App\Presentation\Http\Admin\Survey\ListSurveysAction;
use App\Presentation\Http\Admin\Survey\UpdateResponseAction as AdminUpdateResponseAction;
use App\Presentation\Http\Admin\Survey\UpdateSurveyAction;
use App\Presentation\Http\Admin\Respondent\ListRespondentsAction;
use App\Presentation\Http\Admin\Respondent\GetRespondentAction as AdminGetRespondentAction;
use App\Presentation\Http\Admin\Respondent\UpdateRespondentAction as AdminUpdateRespondentAction;
use App\Presentation\Http\Admin\Respondent\DeleteRespondentAction;
use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\Liff\IdentifyAction;
use App\Presentation\Http\Liff\IdentifyManualAction;
use App\Presentation\Http\Liff\LogoutAction;
use App\Presentation\Http\Respondent\GetRespondentAction;
use App\Presentation\Http\Respondent\UpdateRespondentAction;
use App\Presentation\Http\Middleware\AuthSessionMiddleware;
use App\Presentation\Http\Middleware\BasicAuthMiddleware;
use App\Presentation\Http\Middleware\RequestSafetyMiddleware;
use App\Presentation\Http\Middleware\SessionMiddleware;
use App\Presentation\Http\Survey\GetPublicSurveyAction;
use App\Presentation\Http\Survey\SaveResponseAction;
use App\Presentation\Http\Survey\GetCurrentResponseAction;
use App\Presentation\Http\Survey\GetResponseHistoryAction;
use App\Presentation\Http\Survey\GetEditResponseAction;
use App\Presentation\Http\Survey\UpdateResponseAction;
use App\Presentation\Http\Survey\GetResponseDraftAction;
use App\Presentation\Http\Survey\SaveResponseDraftAction;
use App\Presentation\Http\Survey\DeleteResponseDraftAction;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->get('/api/health', function (Request $request, Response $response) {
        return JsonResponse::success($response, ['status' => 'ok']);
    });

    // LIFF & Public Survey APIs
    $app->group('/api', function (RouteCollectorProxy $group) {
        // Identification
        $group->post('/liff/identify', IdentifyAction::class)
            ->add(RequestSafetyMiddleware::class);
        $group->post('/liff/identify/manual', IdentifyManualAction::class)
            ->add(RequestSafetyMiddleware::class);
        $group->post('/liff/logout', LogoutAction::class)
            ->add(RequestSafetyMiddleware::class);

        // Public Survey
        $group->get('/surveys/public/{public_id}', GetPublicSurveyAction::class);

        // Session-required Public Survey APIs
        $group->group('', function (RouteCollectorProxy $sessionGroup) {
            // Respondent Profile
            $sessionGroup->get('/respondent', GetRespondentAction::class);
            $sessionGroup->put('/respondent', UpdateRespondentAction::class)
                ->add(RequestSafetyMiddleware::class);

            $sessionGroup->get('/surveys/responses/history', GetResponseHistoryAction::class);
            $sessionGroup->post('/surveys/public/{public_id}/responses', SaveResponseAction::class)
                ->add(RequestSafetyMiddleware::class);
            $sessionGroup->get('/surveys/public/{public_id}/responses/current', GetCurrentResponseAction::class);
            $sessionGroup->get('/surveys/public/{public_id}/responses/{edit_token}', GetEditResponseAction::class);
            $sessionGroup->put('/surveys/public/{public_id}/responses/{edit_token}', UpdateResponseAction::class)
                ->add(RequestSafetyMiddleware::class);

            // Response Drafts
            $sessionGroup->get('/surveys/public/{public_id}/response-draft', GetResponseDraftAction::class);
            $sessionGroup->put('/surveys/public/{public_id}/response-draft', SaveResponseDraftAction::class)
                ->add(RequestSafetyMiddleware::class);
            $sessionGroup->delete('/surveys/public/{public_id}/response-draft', DeleteResponseDraftAction::class)
                ->add(RequestSafetyMiddleware::class);
        })->add(AuthSessionMiddleware::class);

    })->add(SessionMiddleware::class);

    // Admin API (Basic Auth protected)
    $app->group('/api/admin', function (RouteCollectorProxy $group) {
        $group->get('/surveys', ListSurveysAction::class);
        $group->post('/surveys', CreateSurveyAction::class);
        $group->get('/surveys/{id:[0-9]+}', GetSurveyAction::class);
        $group->get('/surveys/{id:[0-9]+}/summary', GetSurveySummaryAction::class);
        $group->post('/surveys/{id:[0-9]+}/duplicate', DuplicateSurveyAction::class);
        $group->put('/surveys/{id:[0-9]+}', UpdateSurveyAction::class);
        $group->delete('/surveys/{id:[0-9]+}', DeleteSurveyAction::class);
        $group->get('/surveys/{id:[0-9]+}/responses', ListResponsesAction::class);
        $group->get('/surveys/{id:[0-9]+}/responses/{responseId:[0-9]+}', GetResponseAction::class);
        $group->put('/surveys/{id:[0-9]+}/responses/{responseId:[0-9]+}', AdminUpdateResponseAction::class);
        $group->delete('/surveys/{id:[0-9]+}/responses/{responseId:[0-9]+}', DeleteResponseAction::class);
        $group->get('/surveys/{id:[0-9]+}/responses.csv', ExportResponsesCsvAction::class);

        // Response Drafts
        $group->get('/response-drafts', ListResponseDraftsAction::class);
        $group->get('/response-drafts/{id:[0-9]+}', GetResponseDraftAdminAction::class);
        $group->post('/response-drafts/cleanup', CleanupResponseDraftsAction::class);

        // Respondent Masters
        $group->get('/respondent-masters', ListRespondentMastersAction::class);
        $group->post('/respondent-masters', CreateRespondentMasterAction::class);
        $group->get('/respondent-masters/{id:[0-9]+}', GetRespondentMasterAction::class);
        $group->put('/respondent-masters/{id:[0-9]+}', UpdateRespondentMasterAction::class);
        $group->delete('/respondent-masters/{id:[0-9]+}', DeleteRespondentMasterAction::class);
        $group->post('/respondent-masters/import', ImportRespondentMastersAction::class);

        // Respondents
        $group->get('/respondents', ListRespondentsAction::class);
        $group->get('/respondents/{id:[0-9]+}', AdminGetRespondentAction::class);
        $group->put('/respondents/{id:[0-9]+}', AdminUpdateRespondentAction::class);
        $group->delete('/respondents/{id:[0-9]+}', DeleteRespondentAction::class);
    })->add(BasicAuthMiddleware::class);
};
