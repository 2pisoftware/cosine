<?php

use Html\Form\Html5Autocomplete;
use Html\Form\Select;

function tasklist_ALL(Web $w)
{
    $w->ctx('title', 'Task List');
    History::add("List Tasks");
    $w->ctx("title", "Task List");
    $w->ctx('layout-size', 'large');

    // Get filter values
    $reset = Request::string("reset");
    $page = $w->sessionOrRequest("task__page", 1);
    $page_size = $w->sessionOrRequest("task__page-size", 10);
    $sort = $w->sessionOrRequest("task__sort", 'task.id');
    $sort_direction = $w->sessionOrRequest("task__sort-direction", 'desc');

    $assignee_id = $w->sessionOrRequest("task__assignee-id", AuthService::getInstance($w)->user()->id);
    $creator_id = $w->sessionOrRequest("task__creator-id");

    $task_group_id = $w->sessionOrRequest("task__task-group-id");
    $task_type = $w->sessionOrRequest('task__type');
    $task_priority = $w->sessionOrRequest('task__priority');
    $task_status = $w->sessionOrRequest('task__status');
    $is_closed = $w->sessionOrRequest("task__is-closed", 0);
    $dt_from = $w->sessionOrRequest('task__dt-from');
    $dt_to = $w->sessionOrRequest('task__dt-to');
    $filter_urgent = $w->sessionOrRequest('task__filter-urgent', false);

    if (!empty($reset)) {
        $assignee_id = AuthService::getInstance($w)->user()->id;
        $creator_id = null;
        $task_group_id = null;
        $task_type = null;
        $task_priority = null;
        $task_status = null;
        $is_closed = 0;
        $dt_from = null;
        $dt_to = null;
        $filter_urgent = false;

        $w->sessionUnset("task__assignee-id");
        $w->sessionUnset("task__creator-id");
        $w->sessionUnset("task__task-group-id");
        $w->sessionUnset("task__type");
        $w->sessionUnset("task__priority");
        $w->sessionUnset("task__status");
        $w->sessionUnset("task__is-closed");
        $w->sessionUnset("task__dt-from");
        $w->sessionUnset("task__dt-to");
        $w->sessionUnset("task__filter-urgent");
        $w->sessionUnset("task__page");
    }

    // First get the taskgroup
    $taskgroup = null;
    if (!empty($task_group_id)) {
        $taskgroup = TaskService::getInstance($w)->getTaskGroup($task_group_id);
    }

    $w->ctx("page", $page);
    $w->ctx("page_size", $page_size);
    $w->ctx("sort", $sort);
    $w->ctx("sort_direction", $sort_direction);

    $query_object = createQuery(
        $w,
        $task_group_id,
        $assignee_id,
        $creator_id,
        $task_type,
        $task_priority,
        $task_status,
        $dt_from,
        $dt_to,
        $is_closed,
        $filter_urgent,
        $sort,
        $sort_direction
    );

    $query_object->paginate($page, $page_size);

    // Fetch dataset and get model objects for them
    $tasks_result_set = $query_object->fetchAll();
    $task_objects = TaskService::getInstance($w)->getObjectsFromRows("Task", $tasks_result_set);

    $count_query = createQuery(
        $w,
        $task_group_id,
        $assignee_id,
        $creator_id,
        $task_type,
        $task_priority,
        $task_status,
        $dt_from,
        $dt_to,
        $is_closed,
        $filter_urgent
    );
    $count = $count_query->count();

    $w->ctx("total_results", $count);

    $w->ctx('table_header', [
        ['task.id', "ID"],
        ['task.title', "Title"],
        ['task_group.title', "Group"],
        ['task.assignee_id', "Assigned To"],
        ['task.task_type', "Type"],
        ['task.priority', "Priority"],
        ['task.status', "Status"],
        ['task.dt_due', "Due"],
    ]);
    // $w->ctx("table_data", $task_objects);

    // Build the filter and its data
    $taskgroup_data = TaskService::getInstance($w)->getTaskGroupDetailsForUser();
    $filter_assignees = $taskgroup_data["members"];
    array_unshift($filter_assignees, ["Unassigned", "unassigned"]);
    $filter_data = [

        (new Select([
            "name|id" => "task__assignee-id",
            "label" => "Assignee",
            // "selected_option" => !empty($assignee_id) ? $assignee_id : null,
            "options" => $filter_assignees,
            "value" => !empty($assignee_id) ? $assignee_id : null,
        ]))->setSelectedOption(intval($assignee_id)),

        (new Select([
            "name|id" => "task__creator-id",
            "label" => "Creator",
            "selected_option" => !empty($creator_id) ? $creator_id : null,
            "options" => $taskgroup_data["members"],
        ]))->setSelectedOption(intval($creator_id)),

        (new Html5Autocomplete([
            "id|name" => "task__task-group-id",
            "label" => "Task Group",
            "placeholder" => "Search",
            "value" => !empty($taskgroup->id) ? $taskgroup->getSelectOptionValue() : null,
            "source" => $w->localUrl("/task-group/ajaxAutocompleteTaskgroups"),
            "minLength" => 2,
        ])),

        (new Select([
            "name|id" => "task__type",
            "label" => "Task Type",
            "selected_option" => !empty($task_type) ? $task_type : null,
            "options" => $taskgroup_data["types"],
        ]))->setSelectedOption($task_type),

        (new Select([
            "name|id" => "task__priority",
            "label" => "Task Priority",
            "selected_option" => !empty($filter_urgent) ? "Urgent" : (!empty($task_priority) ? $task_priority : null),
            "options" => $taskgroup_data["priorities"],
        ]))->setSelectedOption($task_priority),

        (new Select([
            "name|id" => "task__status",
            "label" => "Task Status",
            "selected_option" => !empty($task_status) ? $task_status : null,
            "options" => $taskgroup_data["statuses"],
        ]))->setSelectedOption($task_status),

        (new Select([
            "label"   => "Closed",
            "name"    => "task__is-closed",
            "id"      => "task__is_closed",
            "options" => [
                ["label" => "No", "value" => '0'],
                ["label" => "Yes", "value" => '1'],
                ["label" => "Both", "value" => '2']
            ],
            "selected_option" => $is_closed,
        ]))->setSelectedOption($is_closed),
    ];

    $w->ctx("filter_data", $filter_data);
    $w->ctx("table_data", array_map(fn(Task $item) => [
        $item->id,
        $item->toLink(),
        $item->getTaskGroupTypeTitle(),
        TaskService::getInstance($w)->getUserById($item->assignee_id),
        $item->getTypeTitle(),
        $item->priority,
        $item->status,
        $item->isTaskLate()
    ], $task_objects));
}

// not a very nice looking signature, sorry
function createQuery(
    Web $w,
    $task_group_id,
    $assignee_id,
    $creator_id,
    $task_type,
    $task_priority,
    $task_status,
    $dt_from,
    $dt_to,
    $is_closed,
    $filter_urgent,
    $sort = 'task.id',
    $sort_direction = 'desc',
) {
    /**
     * @var DbPDO
     */
    $query_object = $w->db->get("task")->leftJoin("task_group")->where("task_group.is_deleted", 0);

    // We can now make ID queries directly to the task_group table because of left join
    if (!empty($task_group_id)) {
        $query_object->where("task.task_group_id", $task_group_id);
    }

    // Repeat above for everything else
    if (!empty($assignee_id)) {
        // Unassigned has a value of 'unassigned' in filter but 0 in db
        if ($assignee_id == 'unassigned') {
            $query_object->where("task.assignee_id", 0);
        } else {
            $query_object->where("task.assignee_id", $assignee_id);
        }
    }
    if (!empty($creator_id)) {
        $query_object->leftJoin("object_modification on object_modification.object_id = task.id and object_modification.table_name = 'task'")
            ->where("object_modification.creator_id", $creator_id);
    }
    if (!empty($task_type)) {
        $query_object->where("task.task_type", $task_type);
    }
    if (!empty($task_priority)) {
        $query_object->where("task.priority", $task_priority);
    }
    if (!empty($task_status)) {
        $query_object->where("task.status", $task_status);
    }

    if ($is_closed !== "2") {
        $query_object->where("task.is_closed", $is_closed == "1");
    }

    // This part is why we want to make our query manually
    if (!empty($dt_from)) {
        if ($dt_from == "NULL") {
            $query_object->where("task.dt_due", null);
        } else {
            $query_object->where("task.dt_due >= ?", $dt_from);
        }
    }

    if (!empty($dt_to)) {
        if ($dt_to == "NULL") {
            $query_object->where("task.dt_due", null);
        } else {
            $query_object->where("task.dt_due <= ?", $dt_to);
        }
    }

    if ($filter_urgent) {
        // 'urgent' tasks have a urgent task type
        // the urgent task types are defined by it's task group
        // so in order to filter by urgent tasks in the database,
        // we need to give a full list of all urgent task types
        // that sucks, but luckily there aren't many task types.
        // if there are ever in the future, they should be moved
        // to the database rather than solely in php

        $groupTypes = [];
        foreach (Config::get("task") as $key => $value) {
            if (!startsWith($key, "TaskGroupType_")) {
                continue;
            }

            $groupTypes[] = $value["urgent-priorities"];
        }

        $groupTypes = array_unique(array_merge(...$groupTypes));

        $query_object->where("task.task_type", array_values($groupTypes));
    }

    $query_object->where("task.is_active", 1);

    // Standard wheres
    $query_object->where("task.is_deleted", [0, null]); //->where("task_group.is_active", 1)->where("task_group.is_deleted", 0);

    $query_object->orderBy("$sort $sort_direction");

    $sql = $query_object->getSql();

    return $query_object;
}
