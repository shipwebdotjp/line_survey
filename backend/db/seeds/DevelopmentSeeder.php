<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class DevelopmentSeeder extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Insert owner user for development data
        $this->execute("INSERT INTO users (line_user_id, line_display_name, role, created_at, updated_at) VALUES ('dev-admin', '開発管理者', 'admin', '$now', '$now')");
        $rows = $this->fetchAll("SELECT id FROM users WHERE line_user_id = 'dev-admin' LIMIT 1");
        $ownerUserId = (int)$rows[0]['id'];

        // Insert survey
        $surveys = $this->table('surveys');
        $surveyData = [
            [
                'owner_user_id' => $ownerUserId,
                'public_id' => 'sv_p7Kf92LpQaXz3MnT8bQYv2A',
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
                'owner_user_id' => $ownerUserId,
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
                'owner_user_id' => $ownerUserId,
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
                'owner_user_id' => $ownerUserId,
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
