<?php

class PasskeyNicknamesMigration extends CmfiveMigration
{
    public function up()
    {
        $this->addColumnToTable("web_authn_credential", "name", "string", ["null" => true]);
    }

    public function down()
    {
        $this->removeColumnFromTable("web_authn_credential", "name");
    }
}
