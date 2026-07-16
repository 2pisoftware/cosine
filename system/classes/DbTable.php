<?php
class DbTable extends DbService
{

    private $name;
    private $mode; // create, update, delete, drop
    private $fields = [];


    public function exists()
    {
    }

    public function addField($name, $type, $not_null = false, $default = null)
    {
    }

    public function renameField($oldname, $newname)
    {
    }

    public function dropField($name)
    {
    }

    public function execute()
    {
    }
}
