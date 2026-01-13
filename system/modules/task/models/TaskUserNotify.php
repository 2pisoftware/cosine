<?php
// defines default Task Notification Matrix as set by Task Group settings
class TaskUserNotify extends DbObject
{
    public $user_id;
    public $task_id;
    public $task_creation;
    public $task_details;
    public $task_comments;
    public $time_log;
    public $task_documents;
    public $task_pages;

    public function getDbTableName()
    {
        return "task_user_notify";
    }
}
