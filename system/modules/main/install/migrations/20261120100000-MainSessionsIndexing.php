<?php

class MainSessionsIndexing extends CmfiveMigration
{
    public function up()
    {
        $this->addIndexToTable('sessions', 'session_id');
        $this->addIndexToTable('sessions', 'expires');

        $this->changeColumnInTable('sessions', 'session_data', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM]);
    }

    public function down()
    {
        $this->removeIndexFromTable('sessions', 'session_id');
        $this->removeIndexFromTable('sessions', 'expires');

        $this->changeColumnInTable('sessions', 'session_data', 'text');
    }

    public function description()
    {
        return "Indexes the sessions table and increases the size of session data for database sessions";
    }
}
