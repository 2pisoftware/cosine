<?php

class FormInitialMigration extends CmfiveMigration
{
    public function up()
    {
        // UP
        if (!$this->hasTable('form')) {
            $this->tableWithId('form')
                ->addColumn("title", "string", ["limit" => 255])
                ->addColumn("description", "string", ["limit" => 1024, 'null' => true])
                ->addCmfiveParameters()
                ->create();
        }

        if (!$this->hasTable('form_field')) {
            $this->tableWithId('form_field')
                ->addColumn("form_id", "biginteger")
                ->addColumn("name", "string", ["limit" => 255])
                ->addColumn("technical_name", "string", ["limit" => 255, 'null' => true])
                ->addColumn("interface_class", "string", ["limit" => 255, "null" => true])
                ->addColumn("type", "string", ["limit" => 255])
                ->addColumn("mask", "string", ["limit" => 1024, "null" => true])
                ->addCmfiveParameters()
                ->create();
        }

        if (!$this->hasTable('form_value')) {
            $this->tableWithId('form_value')
                ->addColumn("form_instance_id", "biginteger")
                ->addColumn("form_field_id", "biginteger")
                ->addColumn("value", "string", ["limit" => 1024, "null" => true])
                ->addColumn("field_type", "string", ["limit" => 255])
                ->addColumn("mask", "string", ["limit" => 1024, "null" => true])
                ->addCmfiveParameters()
                ->create();
        }

        if (!$this->hasTable('form_field_metadata')) {
            $this->tableWithId('form_field_metadata')
                ->addColumn("form_field_id", "biginteger")
                ->addColumn("meta_key", "string", ["limit" => 255])
                ->addColumn("meta_value", "string", ["limit" => 255, "null" => true])
                ->addCmfiveParameters()
                ->create();
        }

        if (!$this->hasTable('form_instance')) {
            $this->tableWithId('form_instance')
                ->addColumn("form_id", "biginteger")
                ->addColumn("object_class", "string", ["limit" => 255])
                ->addColumn("object_id", "biginteger")
                ->addCmfiveParameters()
                ->create();
        }

        if (!$this->hasTable('form_mapping')) {
            $this->tableWithId('form_mapping')
                ->addColumn("form_id", "biginteger")
                ->addColumn("object", "string", ["limit" => 255])
                ->addCmfiveParameters()
                ->create();
        }
    }

    public function down()
    {
        // DOWN
        $this->hasTable("form") ? $this->dropTable("form") : null;
        $this->hasTable("form_field") ? $this->dropTable("form_field") : null;
        $this->hasTable("form_value") ? $this->dropTable("form_value") : null;
        $this->hasTable("form_field_metadata") ? $this->dropTable("form_field_metadata") : null;
        $this->hasTable("form_instance") ? $this->dropTable("form_instance") : null;
        $this->hasTable("form_mapping") ? $this->dropTable("form_mapping") : null;
    }
}
