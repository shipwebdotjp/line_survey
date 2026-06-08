<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialMigration extends AbstractMigration
{
    public function up(): void
    {
        // surveys
        $table = $this->table('surveys');
        $table->addColumn('public_id', 'string', ['limit' => 64])
              ->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('questions_json', 'json')
              ->addColumn('status', 'string', ['limit' => 20, 'default' => 'draft'])
              ->addColumn('allow_multiple', 'boolean', ['default' => false])
              ->addColumn('allow_edit', 'boolean', ['default' => false])
              ->addColumn('starts_at', 'datetime', ['null' => true])
              ->addColumn('ends_at', 'datetime', ['null' => true])
              ->addColumn('send_confirmation_email', 'boolean', ['default' => true])
              ->addColumn('include_answers_in_email', 'boolean', ['default' => true])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['public_id'], ['unique' => true])
              ->create();

        // respondent_masters
        $table = $this->table('respondent_masters');
        $table->addColumn('master_code', 'string', ['limit' => 100])
              ->addColumn('line_display_name', 'string', ['limit' => 255])
              ->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('email', 'string', ['limit' => 255])
              ->addColumn('honorific', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('note', 'text', ['null' => true])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['master_code'], ['unique' => true])
              ->addIndex(['line_display_name'], ['unique' => true])
              ->create();

        // respondents
        $table = $this->table('respondents');
        $table->addColumn('line_user_id', 'string', ['limit' => 255])
              ->addColumn('line_display_name', 'string', ['limit' => 255])
              ->addColumn('respondent_master_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('email', 'string', ['limit' => 255])
              ->addColumn('honorific', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('is_manually_entered', 'boolean', ['default' => false])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['line_user_id'], ['unique' => true])
              ->addIndex(['respondent_master_id'])
              ->create();

        // responses
        $table = $this->table('responses');
        $table->addColumn('survey_id', 'integer', ['signed' => false])
              ->addColumn('respondent_id', 'integer', ['signed' => false])
              ->addColumn('edit_token', 'string', ['limit' => 128])
              ->addColumn('answer_json', 'json')
              ->addColumn('survey_snapshot_json', 'json', ['null' => true])
              ->addColumn('submitted_at', 'datetime')
              ->addColumn('email_sent_at', 'datetime', ['null' => true])
              ->addColumn('email_error', 'text', ['null' => true])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['edit_token'], ['unique' => true])
              ->addIndex(['survey_id'])
              ->addIndex(['respondent_id'])
              ->addIndex(['survey_id', 'respondent_id'], ['name' => 'idx_survey_respondent'])
              ->addForeignKey('survey_id', 'surveys', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
              ->addForeignKey('respondent_id', 'respondents', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
              ->create();
    }

    public function down(): void
    {
        $this->table('responses')->drop()->save();
        $this->table('respondents')->drop()->save();
        $this->table('respondent_masters')->drop()->save();
        $this->table('surveys')->drop()->save();
    }
}
