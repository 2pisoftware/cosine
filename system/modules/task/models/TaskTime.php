<?php
class TaskTime extends DbObject
{
    public $task_id;
    public $creator_id;
    public $dt_created;
    public $user_id;
    public $dt_start;
    public $dt_end;
    public $comment_id;
    public $is_suspect;
    public $is_deleted;
    public $time_type;

    public static $_db_table = "task_time";

    public function getDuration()
    {
        if (!empty($this->dt_start) and !empty($this->dt_end)) {
            return ($this->dt_end - $this->dt_start);
        }
    }

    public function getComment()
    {
        if (!empty($this->comment_id)) {
            return CommentService::getInstance($this->w)->getComment($this->comment_id);
        }
    }

    public function getTask()
    {
        return $this->getObject("Task", $this->task_id);
    }
}
