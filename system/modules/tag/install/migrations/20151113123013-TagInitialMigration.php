<?php

class TagInitialMigration extends CmfiveMigration
{
    public function up()
    {
        if (!$this->hasTable("tag")) {
            $this->tableWithId('tag')
                ->addColumn('user_id', 'biginteger')
                ->addColumn('obj_class', 'string', ['limit' => 200])
                ->addColumn('obj_id', 'biginteger', ['null' => true])
                ->addColumn('tag', 'string', ['limit' => 255])
                ->addColumn('tag_color', 'string', ['limit' => 255])
                ->addCmfiveParameters()
                ->addIndex(['is_deleted', 'tag', 'obj_class', 'obj_id', 'user_id'])
                ->create();
        }
    }

    public function down()
    {
        $this->hasTable("tag") ? $this->dropTable('tag') : null;
    }
}
