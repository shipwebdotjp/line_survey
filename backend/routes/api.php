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
use App\Presentation\Http\Admin\LoginAction as AdminLoginAction;
use App\Presentation\Http\Admin\LogoutAction as AdminLogoutAction;
use App\Presentation\Http\Admin\MeAction as AdminMeAction;
use App\Presentation\Http\Admin\UpdateMeAction;
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
use App\Presentation\Http\Middleware\AdminAuthMiddleware;
use App\Presentation\Http\Liff\IdentifyAction;
use App\Presentation\Http\Liff\IdentifyManualAction;
use App\Presentation\Http\Liff\LogoutAction;
use App\Presentation\Http\Respondent\GetRespondentAction;
use App\Presentation\Http\Respondent\UpdateRespondentAction;
use App\Presentation\Http\Middleware\AuthSessionMiddleware;
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

    // Manage API
    $app->group('/api/manage', function (RouteCollectorProxy $group) {
        // Public Admin APIs (No session required)
        $group->post('/login', AdminLoginAction::class);

        // Session-required Admin APIs
        $group->group('', function (RouteCollectorProxy $adminGroup) {
            $adminGroup->post('/logout', AdminLogoutAction::class);
            $adminGroup->get('/me', AdminMeAction::class);
            $adminGroup->put('/me', UpdateMeAction::class);

            $adminGroup->get('/surveys', ListSurveysAction::class);
            $adminGroup->post('/surveys', CreateSurveyAction::class);
            $adminGroup->get('/surveys/{id:[0-9]+}', GetSurveyAction::class);
            $adminGroup->get('/surveys/{id:[0-9]+}/summary', GetSurveySummaryAction::class);
            $adminGroup->post('/surveys/{id:[0-9]+}/duplicate', DuplicateSurveyAction::class);
            $adminGroup->put('/surveys/{id:[0-9]+}', UpdateSurveyAction::class);
            $adminGroup->delete('/surveys/{id:[0-9]+}', DeleteSurveyAction::class);
            $adminGroup->get('/surveys/{id:[0-9]+}/responses', ListResponsesAction::class);
            $adminGroup->get('/surveys/{id:[0-9]+}/responses/{responseId:[0-9]+}', GetResponseAction::class);
            $adminGroup->put('/surveys/{id:[0-9]+}/responses/{responseId:[0-9]+}', AdminUpdateResponseAction::class);
            $adminGroup->delete('/surveys/{id:[0-9]+}/responses/{responseId:[0-9]+}', DeleteResponseAction::class);
            $adminGroup->get('/surveys/{id:[0-9]+}/responses.csv', ExportResponsesCsvAction::class);

            // Response Drafts
            $adminGroup->get('/response-drafts', ListResponseDraftsAction::class);
            $adminGroup->get('/response-drafts/{id:[0-9]+}', GetResponseDraftAdminAction::class);
            $adminGroup->post('/response-drafts/cleanup', CleanupResponseDraftsAction::class);

            // Respondent Masters
            $adminGroup->get('/respondent-masters', ListRespondentMastersAction::class);
            $adminGroup->post('/respondent-masters', CreateRespondentMasterAction::class);
            $adminGroup->get('/respondent-masters/{id:[0-9]+}', GetRespondentMasterAction::class);
            $adminGroup->put('/respondent-masters/{id:[0-9]+}', UpdateRespondentMasterAction::class);
            $adminGroup->delete('/respondent-masters/{id:[0-9]+}', DeleteRespondentMasterAction::class);
            $adminGroup->post('/respondent-masters/import', ImportRespondentMastersAction::class);

            // Respondents
            $adminGroup->get('/respondents', ListRespondentsAction::class);
            $adminGroup->get('/respondents/{id:[0-9]+}', AdminGetRespondentAction::class);
            $adminGroup->put('/respondents/{id:[0-9]+}', AdminUpdateRespondentAction::class);
            $adminGroup->delete('/respondents/{id:[0-9]+}', DeleteRespondentAction::class);
        })->add(AdminAuthMiddleware::class);
    })->add(RequestSafetyMiddleware::class)
      ->add(SessionMiddleware::class);
};
