<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Survey;

use App\Application\Admin\Survey\ExportResponsesCsvUseCase;
use App\Infrastructure\Csv\SurveyResponseCsvExporter;
use App\Infrastructure\Database\ResponseRepository;
use App\Infrastructure\Database\SurveyRepository;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ExportResponsesCsvUseCaseTest extends TestCase
{
    private $surveyRepository;
    private $responseRepository;
    private $csvExporter;
    private $useCase;
    private $request;

    protected function setUp(): void
    {
        $this->surveyRepository = $this->createMock(SurveyRepository::class);
        $this->responseRepository = $this->createMock(ResponseRepository::class);
        $this->csvExporter = $this->createMock(SurveyResponseCsvExporter::class);
        $this->useCase = new ExportResponsesCsvUseCase(
            $this->surveyRepository,
            $this->responseRepository,
            $this->csvExporter
        );
        $this->request = $this->createMock(ServerRequestInterface::class);
    }

    public function testExecuteReturnsCsvString(): void
    {
        $surveyId = 1;
        $survey = ['id' => $surveyId];
        $responses = [['id' => 101]];
        $csvContent = "header,data\n";

        $this->surveyRepository->expects($this->once())
            ->method('findById')
            ->with($surveyId)
            ->willReturn($survey);

        $this->responseRepository->expects($this->once())
            ->method('findBySurveyIdWithRespondent')
            ->with($surveyId)
            ->willReturn($responses);

        $this->csvExporter->expects($this->once())
            ->method('export')
            ->with($survey, $responses)
            ->willReturn($csvContent);

        $result = $this->useCase->execute($surveyId, $this->request);

        $this->assertEquals($csvContent, $result);
    }
}
