<?php

Config::set('task', [
    'active' => true,
    'path' => 'system/modules',
    'topmenu' => true,
    'search' => ['Tasks' => "Task"],
    'hooks' => [
        'core_web',
        'core_dbobject',
        'comment',
        'attachment',
        'timelog',
        'admin',
        'task'
    ],
    'ical' => [
        'send' => false
    ],
    'timelog' => [
        'Task'
    ],
    'processors' => [
        'TicketEmailProcessor'
    ]
]);

// Set form mapping objects
Config::append('form.mapping', [
    'Task', 'TaskGroup'
]);

//========= Properties of Task Type Todo ==================

Config::append('task.TaskType_Todo', [
    'time-type' => ["Ordinary Hours", "Overtime", "Weekend"],
]);

//========= Properties of Taskgroup Type Todo ============

Config::append('task.TaskGroupType_TaskTodo', [
    'title' => 'To Do',
    'description' => 'This is a TODO list. Use this for assigning any work.',
    'can-task-reopen' => true,
    'tasktypes' => ["Todo" => "To Do"],
    'statuses' => [
        ["New", false],
        ["Assigned", false],
        ["Wip", false],
        ["Pending", false],
        ["Done", true], // is closing
        ["Rejected", true]
    ], // is closing
    'priorities' => ["Urgent", "Normal", "Nice to have"],
    'urgent-priorities' => ["Urgent"]
]);

//========= Properties of Task Type Programming Task =================

Config::append('task.TaskType_ProgrammingTicket', [
    'time-type' => ["Ordinary Hours", "Overtime", "Weekend"],
]);

//========= Properties of Taskgroup Type SoftwareDevelopment ==

Config::append('task.TaskGroupType_SoftwareDevelopment', [
    'title' => 'Software Development',
    'description' => 'Use this for tracking software development tasks.',
    'can-task-reopen' => true,
    'tasktypes' => [
        "ProgrammingTicket" => "Programming Task",
    ],
    'statuses' => [
        ["Idea", false],
        ["On Hold", false],
        ["Backlog", false],
        ["Todo", false],
        ["WIP", false],
        ["Testing", false],
        ["Review", false],
        ["Deploy", false],
        ["Live", true], // is closing
        ["Rejected", true],
    ], // is closing
    'priorities' => ["Urgent", "Normal", "Nice to have"],
    'urgent-priorities' => ["Urgent"]
]);

Config::set('task.TaskGroupType_CmfiveSupport', [
    'title' => 'Cmfive Support',
    'description' => 'Tracking Support Requests.',
    'can-task-reopen' => true,
    'tasktypes' => ["CmfiveTicket" => "Support Ticket"],
    'statuses' => [
        ["New", false],
        ["Assigned", false],
        ["WIP", false],
        ["Wait for Comment", false],
        ["Done", true], // is closing
        ["Rejected", true]
    ], // is closing
    'priorities' => ["Critical","Major", "Minor", "Normal"],
    'urgent-priorities' => ["Critical", "Major"]
]);

Config::set('task.TaskType_CmfiveTicket', [
    'title' => "Support Ticket",
    'description' => "A Support Ticket.",
    'time-types' => ["Business Hours", "After Hours", "Quoted", "Non-Billable", "Internal"]
]);
