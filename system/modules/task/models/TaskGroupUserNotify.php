<?php

// defines default Task Group Notification Matrix as set by OWNER
class TaskGroupUserNotify extends DbObject
{
    public $user_id;
    public $task_group_id;
    public $role;
    public $type;
    public $value;
    public $task_creation;
    public $task_details;
    public $task_comments;
    public $time_log;
    public $task_documents;
    public $task_pages;

    public static $_db_table = "task_group_user_notify";
}
