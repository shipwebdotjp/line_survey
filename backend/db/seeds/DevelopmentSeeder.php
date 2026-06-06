<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class DevelopmentSeeder extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Truncate tables to ensure idempotency and fresh state
        // Disable foreign key checks to allow truncation of tables with relationships
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->execute('TRUNCATE TABLE responses');
        $this->execute('TRUNCATE TABLE respondents');
        $this->execute('TRUNCATE TABLE respondent_masters');
        $this->execute('TRUNCATE TABLE surveys');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');

        // Insert survey
        $surveys = $this->table('surveys');
        $surveyData = [
            [
                'public_id' => 'sample-survey-2024',
                'title' => '開発用サンプルアンケート',
                'description' => 'これは開発用のサンプルアンケートです。',
                'questions_json' => json_encode([
                    'pages' => [
                        [
                            'name' => 'page1',
                            'elements' => [
                                [
                                    'type' => 'text',
                                    'name' => 'question1',
                                    'title' => 'お名前を教えてください',
                                ],
                                [
                                    'type' => 'radiogroup',
                                    'name' => 'question2',
                                    'title' => '当サービスを知ったきっかけは何ですか？',
                                    'choices' => ['SNS', '検索エンジン', '知人の紹介', 'その他'],
                                ],
                            ],
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'status' => 'published',
                'allow_multiple' => false,
                'allow_edit' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];
        $surveys->insert($surveyData)->saveData();

        // Insert respondent_masters
        $masters = $this->table('respondent_masters');
        $masterData = [
            [
                'master_code' => 'M001',
                'line_display_name' => 'テスト太郎',
                'name' => 'テスト 太郎',
                'email' => 'taro@example.com',
                'honorific' => '様',
                'note' => '主要なテストユーザーです。',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'master_code' => 'M002',
                'line_display_name' => 'サンプル花子',
                'name' => 'サンプル 花子',
                'email' => 'hanako@example.com',
                'honorific' => '様',
                'note' => '予備のテストユーザーです。',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'master_code' => 'M003',
                'line_display_name' => 'デモ次郎',
                'name' => 'デモ 次郎',
                'email' => 'jiro@example.com',
                'honorific' => '君',
                'note' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $masters->insert($masterData)->saveData();
    }
}
