<?php

class WebAuthnCounter extends CmfiveMigration
{
    public function up()
    {
        $this->addColumnToTable("web_authn_credential", "counter", "integer", ["default" => 0]);
    }

    public function down()
    {
        $this->removeColumnFromTable("web_authn_credential", "counter");
    }
}
