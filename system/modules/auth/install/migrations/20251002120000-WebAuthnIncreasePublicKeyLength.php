<?php

class WebAuthnIncreasePublicKeyLength extends CmfiveMigration
{
    public function up()
    {
        $this->changeColumnInTable("web_authn_credential", "publicKey", "string", ["length" => 2048]);
    }

    public function down()
    {
        $this->changeColumnInTable("web_authn_credential", "publicKey", "string", ["length" => 512]);
    }
}
