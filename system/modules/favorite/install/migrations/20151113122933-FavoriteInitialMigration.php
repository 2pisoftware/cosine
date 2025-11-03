<?php

class FavoriteInitialMigration extends CmfiveMigration
{
    public function up()
    {
        if (!$this->hasTable('favorite')) {
            $this->tableWithId('favorite')
                ->addColumn('object_class', 'string', ['limit' => 255])
                ->addColumn('object_id', 'biginteger')
                ->addColumn('user_id', 'biginteger')
                ->addCmfiveParameters()
                ->create();
        }
    }

    public function down()
    {
        $this->hasTable('favorite') ? $this->dropTable('favorite') : null;
    }
}
