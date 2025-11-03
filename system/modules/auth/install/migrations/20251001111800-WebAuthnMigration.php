<?php

class WebAuthnMigration extends CmfiveMigration
{
    public function up()
    {
        $column = parent::Column();
        $column->setName('id')
            ->setType('biginteger')
            ->setIdentity(true);

        if (!$this->hasTable("web_authn_credential")) {
            $this->tableWithId("web_authn_credential")
                ->addStringColumn("type")
                ->addStringColumn("credentialId")
                ->addStringColumn("transports")
                ->addStringColumn("user_id")
                ->addStringColumn("attestationType")
                ->addStringColumn("aaguid")
                ->addStringColumn("publicKey")
                ->addCmfiveParameters()
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('web_authn_credential')) {
            $this->dropTable('web_authn_credential');
        }
    }
}
