<?php

function editComment_GET(Web &$w)
{
    $p = $w->pathMatch("taskid", "comm_id");

    // get the relevant comment
    $comm = TaskService::getInstance($w)->getComment($p['comm_id']);

    // build the comment for edit
    $form = [
        ["Comment", "section"],
        ["", "textarea", "comment", strip_tags($comm->comment ?? ""), 45, 25],
    ];

    // return the comment for display and edit
    $form = HtmlBootstrap5::form($form, $w->localUrl("/task/editComment/".$p['taskid']."/".$p['comm_id']), "POST", "Save");
    $w->setLayout(null);
    $w->out($form);
}

function popComment_GET(Web &$w)
{
    $p = $w->pathMatch("taskid", "comm_id");

    // get the relevant comment
    $comm = TaskService::getInstance($w)->getComment($p['comm_id']);

    // build the comment for display
    $form = [
        ["Comment", "section"],
        ["", "textarea", "comment", strip_tags($comm->comment ?? ""), 45, 25],
    ];

    // return the comment for display
    $form = HtmlBootstrap5::form($form);
    $w->setLayout(null);
    $w->out($form);
}

function editComment_POST(Web $w)
{
    $p = $w->pathMatch("taskid", "comm_id");
    $task = TaskService::getInstance($w)->getTask($p['taskid']);

    // convert any HTML to entities for display
    $_REQUEST['comment'] = htmlspecialchars($_REQUEST['comment']);

    // get the relevant comment
    $comm = TaskService::getInstance($w)->getComment($p['comm_id']);

    // if comment exists, update it. if not, create it.
    if ($comm) {
        $comm->fill($_REQUEST);
        $comm->update();
        $commsg = "Comment updated.";
    } else {
        $comm = new TaskComment($w);
        $comm->fill($_REQUEST);
        $comm->obj_table = $task->getDbTableName();
        $comm->obj_id = $p['taskid'];
        $comm->insert();
        $commsg = "Comment created.";
    }
    // add to context for notifications post listener
    $w->ctx("TaskComment", $comm);
    $w->ctx("TaskEvent", "task_comments");

    // return
    $w->msg($commsg, "/task/edit/".$p['taskid']."#timelog");
}

function attachForm_GET(Web $w)
{
    $p = $w->pathMatch("id");

    // get relevant task
    $task = TaskService::getInstance($w)->getTask($p['id']);

    // build form to upload document/attachment
    $form = [
        ["Attach Document", "section"],
        ["Document", "file", "form"],
        ["Description", "textarea", "description", null, "26", "6"],
    ];

    // diplay form
    $form = HtmlBootstrap5::form($form, $w->localUrl("/task/attachForm/".$task->id), "POST", " Upload ", null, null, null, 'multipart/form-data');

    $w->setLayout(null);
    $w->out($form);
}

function attachForm_POST(Web $w)
{
    $p = $w->pathMatch("id");

    // get relevant task
    $task = TaskService::getInstance($w)->getTask($p['id']);

    // if task exists get REQUEST and FILE object for insert into attachment database against this task
    if ($task) {
        $description = Request::string('description');

        if ($_FILES['form']['size'] > 0) {
            $filename = strtolower($_FILES['form']['name']);
            $parts = explode(".", $filename);
            $n = count($parts) - 1;
            $ext = $parts[$n];

            $attach = FileService::getInstance($w)->uploadAttachment("form", $task, null, $description);
            if (!$attach) {
                $message = "There was an error. The document could not be saved.";
            } else {
                $message = "The Document has been uploaded.";
            }
        }

        // create comment
        $comm = new TaskComment($w);
        $comm->obj_table = $task->getDbTableName();
        $comm->obj_id = $task->id;
        $comm->comment = "File Uploaded: ".$filename;
        $comm->insert();

        // add to context for notifications post listener
        $w->ctx("TaskComment", $comm);
        $w->ctx("TaskEvent", "task_documents");
    }

    // return
    $w->msg($message, "/task/edit/".$task->id."#attachments");
}

//////////////////////////////////////
//       TASK NOTIFICATIONS         //
//////////////////////////////////////

function updateusergroupnotify_GET(Web &$w)
{
    $p = $w->pathMatch("id");

    // get task title
    $title = TaskService::getInstance($w)->getTaskGroupTitleById($p['id']);

    // get member
    $member = TaskService::getInstance($w)->getMemberGroupById($p['id'], $_SESSION['user_id']);

    // get user notify settings for Task Group
    $notify = TaskService::getInstance($w)->getTaskGroupUserNotify($_SESSION['user_id'], $p['id']);
    if ($notify) {
        foreach ($notify as $n) {
            $v[$n->role][$n->type] = $n->value;
            $task_creation = $n->task_creation;
            $task_details = $n->task_details;
            $task_comments = $n->task_comments;
            $time_log = $n->time_log;
            $task_documents = $n->task_documents;
            $task_pages = $n->task_pages;
        }
    } else { // no user notify? get default group settings. set all task events on
        $notify = TaskService::getInstance($w)->getTaskGroupNotify($p['id']);
        if ($notify) {
            foreach ($notify as $n) {
                $v[$n->role][$n->type] = $n->value;
                $task_creation = 1;
                $task_details = 1;
                $task_comments = 1;
                $time_log = 1;
                $task_documents = 1;
                $task_pages = 1;
            }
        }
    }

    // if no user notifications and no group defaults
    // set blank form - all task events on - so user can create their user notifications
    if (!$v) {
        $v['guest']['creator'] = 0;
        $v['member']['creator'] = 0;
        $v['member']['assignee'] = 0;
        $v['owner']['creator'] = 0;
        $v['owner']['assignee'] = 0;
        $v['owner']['other'] = 0;
        $task_creation = 1;
        $task_details = 1;
        $task_comments = 1;
        $time_log = 1;
        $task_documents = 1;
        $task_pages = 1;
    }

    $f = [[$title." - Notifications", "section"]];

    // so foreach role/type lets get the values and create  checkboxes
    foreach ($v as $role => $types) {
        if ($role == strtolower($member->role)) {
            foreach ($types as $type => $value) {
                $f[] = [ucfirst($type), "checkbox", $role."_".$type, $value];
            }
        }
    }

    // add Task Events to form
    $f[] = ["For which events should you receive Notification?", "section"];
    $f[] = ["Task Creation", "checkbox", "task_creation", $task_creation];
    $f[] = ["Task Details Update", "checkbox", "task_details", $task_details];
    $f[] = ["Comments Added", "checkbox", "task_comments", $task_comments];
    $f[] = ["Time Log Entry", "checkbox", "time_log", $time_log];
    $f[] = ["Documents Added", "checkbox", "task_documents", $task_documents];
    $f[] = ["Pages Added", "checkbox", "task_pages", $task_pages];

    $f = HtmlBootstrap5::form($f, $w->localUrl("/task/updateusergroupnotify/".$p['id']), "POST", "Save");

    $w->setLayout(null);
    $w->out($f);
}

function updateusergroupnotify_POST(Web &$w)
{
    $p = $w->pathMatch("id");

    // lets set some values knowing that only checked checkboxes return a value
    $arr['guest']['creator'] = $_REQUEST['guest_creator'] ? $_REQUEST['guest_creator'] : "0";
    $arr['member']['creator'] = $_REQUEST['member_creator'] ? $_REQUEST['member_creator'] : "0";
    $arr['member']['assignee'] = $_REQUEST['member_assignee'] ? $_REQUEST['member_assignee'] : "0";
    $arr['owner']['creator'] = $_REQUEST['owner_creator'] ? $_REQUEST['owner_creator'] : "0";
    $arr['owner']['assignee'] = $_REQUEST['owner_assignee'] ? $_REQUEST['owner_assignee'] : "0";
    $arr['owner']['other'] = $_REQUEST['owner_other'] ? $_REQUEST['owner_other'] : "0";

    // set task event notify values
    $task_creation = $_REQUEST['task_creation'] ? $_REQUEST['task_creation'] : "0";
    $task_details = $_REQUEST['task_details'] ? $_REQUEST['task_details'] : "0";
    $task_comments = $_REQUEST['task_comments'] ? $_REQUEST['task_comments'] : "0";
    $time_log = $_REQUEST['time_log'] ? $_REQUEST['time_log'] : "0";
    $task_documents = $_REQUEST['task_documents'] ? $_REQUEST['task_documents'] : "0";
    $task_pages = $_REQUEST['task_pages'] ? $_REQUEST['task_pages'] : "0";

    // so foreach role/type lets put the values in the database
    foreach ($arr as $role => $types) {
        foreach ($types as $type => $value) {
            // is there a record for this user > taskgroup > role > type?
            $notify = TaskService::getInstance($w)->getTaskGroupUserNotifyType($_SESSION['user_id'], $p['id'], $role, $type);

            // if yes, update, if no, insert
            if ($notify) {
                $notify->value = $value;
                $notify->task_creation = $task_creation;
                $notify->task_details = $task_details;
                $notify->task_comments = $task_comments;
                $notify->time_log = $time_log;
                $notify->task_documents = $task_documents;
                $notify->task_pages = $task_pages;
                $notify->update();
            } else {
                $notify = new TaskGroupUserNotify($w);
                $notify->task_group_id = $p['id'];
                $notify->user_id = $_SESSION['user_id'];
                $notify->role = $role;
                $notify->type = $type;
                $notify->value = $value;
                $notify->task_creation = $task_creation;
                $notify->task_details = $task_details;
                $notify->task_comments = $task_comments;
                $notify->time_log = $time_log;
                $notify->task_documents = $task_documents;
                $notify->task_pages = $task_pages;
                $notify->insert();
            }
        }
    }

    // return
    $w->msg("Notifications Updated", "/task/tasklist/?taskgroups=".$p['id']."&tab=2");
}

function updateusertasknotify_POST(Web &$w)
{
    $p = $w->pathMatch("id");

    // set task event notify values
    $task_creation = $_REQUEST['task_creation'] ? $_REQUEST['task_creation'] : "0";
    $task_details = $_REQUEST['task_details'] ? $_REQUEST['task_details'] : "0";
    $task_comments = $_REQUEST['task_comments'] ? $_REQUEST['task_comments'] : "0";
    $time_log = $_REQUEST['time_log'] ? $_REQUEST['time_log'] : "0";
    $task_documents = $_REQUEST['task_documents'] ? $_REQUEST['task_documents'] : "0";
    $task_pages = $_REQUEST['task_pages'] ? $_REQUEST['task_pages'] : "0";

    // is there a record for this user > task?
    $notify = TaskService::getInstance($w)->getTaskUserNotify($_SESSION['user_id'], $p['id']);

    // if yes, update, if no, insert
    if ($notify) {
        $notify->task_creation = $task_creation;
        $notify->task_details = $task_details;
        $notify->task_comments = $task_comments;
        $notify->time_log = $time_log;
        $notify->task_documents = $task_documents;
        $notify->task_pages = $task_pages;
        $notify->update();
    } else {
        $notify = new TaskUserNotify($w);
        $notify->task_id = $p['id'];
        $notify->user_id = $_SESSION['user_id'];
        $notify->task_creation = $task_creation;
        $notify->task_details = $task_details;
        $notify->task_comments = $task_comments;
        $notify->time_log = $time_log;
        $notify->task_documents = $task_documents;
        $notify->task_pages = $task_pages;
        $notify->insert();
    }

    // return
    $w->msg("Notifications Updated", "/task/edit/".$p['id']."#notification");
}
