<?php

class MainSessionsIndexing extends CmfiveMigration
{
    public function up()
    {
        $this->addIndexToTable('sessions', 'session_id');
    }

    public function down()
    {
        $this->removeIndexFromTable('sessions', 'session_id');
    }

    public function description()
    {
        return "Indexes the session id on the sessions table for database sessions";
    }
}
