<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddForeignKeyToRespondentsTable extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('respondents');
        $table->addForeignKey('respondent_master_id', 'respondent_masters', 'id', [
            'delete' => 'SET_NULL',
            'update' => 'CASCADE'
        ])->save();
    }

    public function down(): void
    {
        $table = $this->table('respondents');
        $table->dropForeignKey('respondent_master_id')->save();
    }
}
