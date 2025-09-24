<style>
    td {
        padding: 5px 20px !important;
    }
</style>

<?php
echo HtmlBootstrap5::filter("Filter Tasks", $filter_data, "/task/tasklist", "GET");

if (!empty($tasks)) {
    $table_header = ["ID", "Title", "Group", "Assigned To", "Type", "Priority", "Status", "Due"];
    $table_data = [];

    // Build table data
    // usort($tasks, array("TaskService", "sortTasksbyDue"));
    foreach ($tasks as $task) {
        if ($task->getCanIView()) {
            $table_data[] = [
                $task->id,
                $task->toLink() . '&nbsp;&nbsp;' . $w->partial('listTags', ['object' => $task, 'limit' => 1], 'tag'),
                $task->getTaskGroup() ? $task->getTaskGroup()->toLink() : null,
                $task->getAssignee() ? $task->getAssignee()->getFullName() : null,
                $task->getTaskTypeObject()?->getTypeTitle() ?? null,
                $task->priority,
                $task->status,
                $task->isTaskLate()
            ];
        }
    }

    echo HtmlBootstrap5::table($table_data, null, "tablesorter", $table_header);
} else {
    echo '<h3><small>No tasks found.</small></h3>';
}
