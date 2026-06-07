<?php

namespace Tests\Backend\Unit;

use PHPUnit\Framework\TestCase;
use App\Application\Survey\GetPublicSurveyUseCase;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Support\DateTimeHelper;

class GetPublicSurveyUseCaseTest extends TestCase
{
    private $repositoryMock;
    private $useCase;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(SurveyRepository::class);
        $this->useCase = new GetPublicSurveyUseCase($this->repositoryMock);
    }

    public function testExecuteReturnsPublishedSurvey()
    {
        $publicId = 'test-id';
        $surveyData = [
            'public_id' => $publicId,
            'title' => 'Test Survey',
            'description' => 'Desc',
            'questions_json' => ['q' => 1],
            'status' => 'published',
            'allow_multiple' => 0,
            'allow_edit' => 0,
            'starts_at' => null,
            'ends_at' => null,
        ];

        $this->repositoryMock->method('findByPublicId')->willReturn($surveyData);

        $result = $this->useCase->execute($publicId);

        $this->assertTrue($result['can_answer']);
        $this->assertNull($result['reason']);
        $this->assertEquals('Test Survey', $result['survey']['title']);
        $this->assertEquals(['q' => 1], $result['survey']['questions_json']);
    }

    public function testExecuteReturnsNotPublished()
    {
        $this->repositoryMock->method('findByPublicId')->willReturn([
            'status' => 'draft',
            'title' => 'Draft',
            'description' => '',
            'questions_json' => [],
            'allow_multiple' => 0,
            'allow_edit' => 0,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $result = $this->useCase->execute('id');
        $this->assertFalse($result['can_answer']);
        $this->assertEquals('not_published', $result['reason']);
    }

    public function testExecuteReturnsNotStarted()
    {
        $tomorrow = DateTimeHelper::nowTokyo()->modify('+1 day')->format('Y-m-d H:i:s');
        $this->repositoryMock->method('findByPublicId')->willReturn([
            'status' => 'published',
            'title' => 'Future',
            'description' => '',
            'questions_json' => [],
            'allow_multiple' => 0,
            'allow_edit' => 0,
            'starts_at' => $tomorrow,
            'ends_at' => null,
        ]);

        $result = $this->useCase->execute('id');
        $this->assertFalse($result['can_answer']);
        $this->assertEquals('not_started', $result['reason']);
    }

    public function testExecuteReturnsClosed()
    {
        $yesterday = DateTimeHelper::nowTokyo()->modify('-1 day')->format('Y-m-d H:i:s');
        $this->repositoryMock->method('findByPublicId')->willReturn([
            'status' => 'published',
            'title' => 'Past',
            'description' => '',
            'questions_json' => [],
            'allow_multiple' => 0,
            'allow_edit' => 0,
            'starts_at' => null,
            'ends_at' => $yesterday,
        ]);

        $result = $this->useCase->execute('id');
        $this->assertFalse($result['can_answer']);
        $this->assertEquals('closed', $result['reason']);
    }

    public function testExecuteReturnsNotFound()
    {
        $this->repositoryMock->method('findByPublicId')->willReturn(null);
        $result = $this->useCase->execute('invalid');
        $this->assertNull($result);
    }
}
