<?php

class TaskTicketService extends DbService
{
    public $taskgroup_type = "TaskGroupType_CmfiveSupport";
    public $task_type = "CmfiveTicket";
    public $taskgroup_name = "Cmfive Support Tickets";

    public function getTaskGroup(): TaskGroup|null
    {
        $taskgroup = TaskService::getInstance($this->w)->getTaskGroupTypeObject("CmfiveSupport");
        if (empty($taskgroup->id)) {
            return TaskService::getInstance($this->w)->createTaskGroup(
                type: $this->taskgroup_type,
                title: $this->taskgroup_name,
                description: "Tickets",
                default_assignee_id: AuthService::getInstance($this->w)->user()->id
            );
        }
        return $taskgroup;
    }
}
