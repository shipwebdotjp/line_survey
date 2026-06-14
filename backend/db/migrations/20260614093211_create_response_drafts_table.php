<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateResponseDraftsTable extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('response_drafts', ['signed' => false]);
        $table->addColumn('survey_id', 'integer', ['signed' => false])
              ->addColumn('respondent_id', 'integer', ['signed' => false])
              ->addColumn('answer_json', 'json')
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['survey_id'])
              ->addIndex(['respondent_id'])
              ->addIndex(['survey_id', 'respondent_id'], ['unique' => true])
              ->addForeignKey('survey_id', 'surveys', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('respondent_id', 'respondents', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->create();
    }

    public function down(): void
    {
        $this->table('response_drafts')->drop()->save();
    }
}
