<?php

namespace System\Modules\Timelog;

function listtimelog(\Web $w, $params)
{
    if (empty($params['object_class']) || empty($params['object_id'])) {
        return;
    }

    $redirect = $params['redirect'] ?: "";

    $target = \TimelogService::getInstance($w)
        ->getObject($params["object_class"], $params["object_id"]);

    $page = \Request::int("p", 1);
    $page_size = \Request::int("ps", 50);

    $count = \TimelogService::getInstance($w)
        ->countTimelogsForObject($target);

    $stmt = $w->db->prepare(
        "SELECT SUM(TIMESTAMPDIFF(SECOND, timelog.dt_start, timelog.dt_end))
        FROM timelog
        WHERE timelog.object_class = ? AND timelog.object_id = ?"
    );
    $stmt->bindValue(1, get_class($target));
    $stmt->bindValue(2, $target->id);
    $stmt->execute();
    $total = intval($stmt->fetchColumn(0));

    $timelogs = \TimelogService::getInstance($w)
        ->getObjects(
            "Timelog",
            [
                "object_class" => get_class($target),
                "object_id" => $target->id,
            ],
            order_by: "dt_start ASC",
            offset: $page_size * ($page - 1),
            limit: $page_size,
        );

    $pagination = \HtmlBootstrap5::pagination(
        currentpage: $page,
        numpages: 0,    // unused
        pagesize: $page_size,
        totalresults: $count,
        baseurl: $w->localUrl($redirect),
    );

    $header = ["Name", "From", "To", "Duration", "Time Type", "Description", "Actions"];
    $table = \HtmlBootstrap5::table(
        data: array_map(function ($val) use ($w, $redirect) {
            $row = [
                $val->getFullName(),
                [formatDate($val->dt_start, "d-m-Y H:i:s"), ["sort" => $val->dt_start]],
                [formatDate($val->dt_end, "d-m-Y H:i:s"), ["sort" => $val->dt_end]],
                [
                    $val->isRunning
                        ? "See Timer"
                        : $val->getHoursWorked() . ':' . str_pad(strval($val->getMinutesWorked()), 2, '0', STR_PAD_LEFT),
                    !$val->isRunning
                        ? ["sort" => $val->getHoursWorked() + ($val->getMinutesWorked() / 60)]
                        : null
                ],
                $val->time_type,
                "<pre class='break-pre text-truncate d-block mt-1 mb-0' style='width: 250px;'>" . strip_tags($val->getComment()->comment) . "</pre>",
            ];

            $actions = [];
            if ($val->canEdit(\AuthService::getInstance($w)->user())) {
                $actions[] = \HtmlBootstrap5::box(
                    href: '/timelog/edit/' . $val->id . (!empty($redirect) ? "?redirect=$redirect" : ''),
                    title: 'Edit',
                    button: true,
                    class: "bg-primary btn-sm"
                );

                $actions[] = \HtmlBootstrap5::box(
                    href: '/timelog/move/' . $val->id . (!empty($redirect) ? "?redirect=$redirect" : ''),
                    title: 'Move',
                    button: true,
                    class: "bg-primary btn-sm"
                );
            }

            if ($val->canDelete(\AuthService::getInstance($w)->user())) {
                $confirmation_message = implode("", $w->callHook("timelog", "before_display_timelog", $val));
                $actions[] = \HtmlBootstrap5::b(
                    href: '/timelog/delete/' . $val->id . (!empty($redirect) ? "?redirect=$redirect" : ''),
                    title: 'Delete',
                    confirm: empty($confirmation_message) ? 'Are you sure you want to delete this timelog?' : $confirmation_message,
                    class: "bg-danger btn-sm"
                );
            }

            $row[] = \HtmlBootstrap5::buttonGroup(implode("", $actions));

            return $row;
        }, $timelogs),
        class: "tablesorter",
        header: $header,
    );

    $w->ctx("pagination", $pagination);
    $w->ctx("table", $table);

    $w->ctx("total", !empty($total) ? $total : 0);
    $w->ctx("class", get_class($target));
    $w->ctx("id", $target->id);
    $w->ctx("redirect", $redirect);
}
