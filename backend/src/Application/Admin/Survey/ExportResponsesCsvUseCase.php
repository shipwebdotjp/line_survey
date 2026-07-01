<?php

declare(strict_types=1);

namespace App\Application\Admin\Survey;

use App\Infrastructure\Csv\SurveyResponseCsvExporter;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;

class ExportResponsesCsvUseCase
{
    public function __construct(
        private SurveyRepository $surveyRepository,
        private ResponseRepository $responseRepository,
        private SurveyResponseCsvExporter $csvExporter
    ) {
    }

    public function execute(int $surveyId, int $ownerUserId, Request $request): string
    {
        $survey = $this->surveyRepository->findById($surveyId, $ownerUserId);
        if (!$survey) {
            throw new HttpNotFoundException($request, 'Survey not found');
        }

        $responses = $this->responseRepository->findBySurveyIdWithRespondent($surveyId);

        return $this->csvExporter->export($survey, $responses);
    }
}
