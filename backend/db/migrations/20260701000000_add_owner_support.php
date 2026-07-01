<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddOwnerSupport extends AbstractMigration
{
    public function up(): void
    {
        // 1. Create users table
        $table = $this->table('users', ['signed' => false]);
        $table->addColumn('line_user_id', 'string', ['limit' => 255])
              ->addColumn('line_display_name', 'string', ['limit' => 255])
              ->addColumn('line_picture_url', 'string', ['limit' => 1024, 'null' => true])
              ->addColumn('role', 'string', ['limit' => 20, 'default' => 'user'])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['line_user_id'], ['unique' => true])
              ->create();

        // 2. Insert initial seed owner
        $now = date('Y-m-d H:i:s');
        $this->execute("INSERT INTO users (line_user_id, line_display_name, role, created_at, updated_at) VALUES ('seed-admin', '初期管理者', 'admin', '$now', '$now')");

        // Get the ID of the seed owner
        $rows = $this->fetchAll("SELECT id FROM users WHERE line_user_id = 'seed-admin'");
        $seedOwnerId = $rows[0]['id'];

        // 3. Add owner_user_id to surveys
        $table = $this->table('surveys');
        $table->addColumn('owner_user_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'id'])
              ->update();

        $this->execute("UPDATE surveys SET owner_user_id = $seedOwnerId");

        $table = $this->table('surveys');
        $table->changeColumn('owner_user_id', 'integer', ['signed' => false, 'null' => false])
              ->addForeignKey('owner_user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->update();

        // 4. Add owner_user_id to respondent_masters and update constraints
        $table = $this->table('respondent_masters');
        $table->addColumn('owner_user_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'id'])
              ->update();

        $this->execute("UPDATE respondent_masters SET owner_user_id = $seedOwnerId");

        $table = $this->table('respondent_masters');
        $table->changeColumn('owner_user_id', 'integer', ['signed' => false, 'null' => false])
              ->removeIndex(['master_code'])
              ->removeIndex(['line_display_name'])
              ->addIndex(['owner_user_id', 'master_code'], ['unique' => true])
              ->addIndex(['line_display_name'])
              ->addForeignKey('owner_user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->update();

        // 5. Add owner_user_id to respondents and update constraints
        $table = $this->table('respondents');
        $table->addColumn('owner_user_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'id'])
              ->update();

        $this->execute("UPDATE respondents SET owner_user_id = $seedOwnerId");

        $table = $this->table('respondents');
        $table->changeColumn('owner_user_id', 'integer', ['signed' => false, 'null' => false])
              ->removeIndex(['line_user_id'])
              ->addIndex(['owner_user_id', 'line_user_id'], ['unique' => true])
              ->addForeignKey('owner_user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->update();
    }

    public function down(): void
    {
        // 1. Remove owner_user_id from respondents and restore constraints
        $table = $this->table('respondents');
        $table->dropForeignKey('owner_user_id')
              ->removeIndex(['owner_user_id', 'line_user_id'])
              ->removeColumn('owner_user_id')
              ->addIndex(['line_user_id'], ['unique' => true])
              ->update();

        // 2. Remove owner_user_id from respondent_masters and restore constraints
        $table = $this->table('respondent_masters');
        $table->dropForeignKey('owner_user_id')
              ->removeIndex(['owner_user_id', 'master_code'])
              ->removeIndex(['line_display_name'])
              ->removeColumn('owner_user_id')
              ->addIndex(['master_code'], ['unique' => true])
              ->addIndex(['line_display_name'], ['unique' => true])
              ->update();

        // 3. Remove owner_user_id from surveys
        $table = $this->table('surveys');
        $table->dropForeignKey('owner_user_id')
              ->removeColumn('owner_user_id')
              ->update();

        // 4. Drop users table
        $this->table('users')->drop()->update();
    }
}
