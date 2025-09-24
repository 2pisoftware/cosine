<?php
class TaskComment extends Comment
{
    // Notifier is called directly because notification should be done for comments inserted from
    // post listener as well.
    public function insert($force_validation = false): void
    {
        parent::insert();

        $this->w->ctx('comment_id', $this->id);
    }
}
