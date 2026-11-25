<?php

class FileS3ObjectSize extends CmfiveMigration
{
    public function up()
    {
        $this->addColumnToTable("file_s3_object", "size", "integer", ["null" => true]);
    }

    public function down()
    {
        $this->removeColumnFromTable("file_s3_object", "size");
    }
}
