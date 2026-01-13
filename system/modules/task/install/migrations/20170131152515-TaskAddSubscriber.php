<?php

class TaskAddSubscriber extends CmfiveMigration
{
    public function up()
    {
        ini_set("max_execution_time", 900);

        // Create task table
        if (!$this->hasTable("task_subscriber")) {
            $this->tableWithId('task_subscriber')
                ->addIdColumn("task_id")
                ->addIdColumn("user_id")
                ->addCmfiveParameters()
                ->create();
        }

        //get all taskgroup id's
        $taskgroup_ids = $this->w->db->get("task_group")->select()->select('id')->where(["is_deleted" => 0, "is_active" => 1])->fetchAll();
        if (!empty($taskgroup_ids)) {
            foreach ($taskgroup_ids as $taskgroup_id) {
                //get taskgroup active member ids
                $member_ids = $this->w->db->get("task_group_member")->select()->select('user_id')->where(["task_group_id" => $taskgroup_id['id'], "is_active" => 1])->fetchAll();
                //get taskgroup open task ids
                $task_ids = $this->w->db->get("task")->select()->select('id')->where(["task_group_id" => $taskgroup_id['id'], "is_closed" => 0])->fetchAll();
                //if tasks and members
                if (!empty($member_ids) && !empty($task_ids)) {
                    foreach ($task_ids as $task_id) {
                        foreach ($member_ids as $member_id) {
                            $subscriber = new TaskSubscriber($this->w);
                            $subscriber->task_id = $task_id['id'];
                            $subscriber->user_id = $member_id['user_id'];
                            $subscriber->insert();
                        }
                    }
                }
            }
        }
    }

    public function down()
    {
        $this->hasTable('task_subscriber') && $this->dropTable('task_subscriber');
    }
}
