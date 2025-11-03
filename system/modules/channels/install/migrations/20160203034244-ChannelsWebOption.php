<?php

class ChannelsWebOption extends CmfiveMigration
{
    public function up()
    {
        if (!$this->hasTable("channel_web_option")) {
            $this->tableWithId("channel_web_option")
                ->addColumn("channel_id", "biginteger")
                ->addColumn("url", "string", ["limit" => 1024])
                ->addCmfiveParameters()
                ->create();
        }
    }

    public function down()
    {
        $this->hasTable("channel_web_option") ? $this->dropTable("channel_web_option") : null;
    }
}
