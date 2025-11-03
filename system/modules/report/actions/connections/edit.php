<?php

function edit_GET(Web $w)
{
    $p = $w->pathMatch("id");
    $report_connection = new ReportConnection($w);
    if (!empty($p["id"])) {
        $report_connection = ReportService::getInstance($w)->getConnection($p["id"]);
    }
    
    if (!empty($report_connection->id)) {
        $report_connection->decrypt();
    }
    
    $form = [
        "Connection" => [
            [
                ["Driver", "select", "db_driver", $report_connection->db_driver, PDO::getAvailableDrivers()],
                ["Host", "text", "db_host", $report_connection->db_host]
            ],
            [
                ["Port", "text", "db_port", $report_connection->db_port],
                ["Database", "text", "db_database", $report_connection->db_database]
            ],
            [
                ["Username", "text", "s_db_user", $report_connection->s_db_user],
                ["Password", "password", "s_db_password", $report_connection->s_db_password]
            ]
        ]
    ];
    
    $w->out(HtmlBootstrap5::multiColForm($form, "/report-connections/edit/{$report_connection->id}"));
}

function edit_POST(Web $w)
{
    $p = $w->pathMatch("id");
    $report_connection = !empty($p["id"]) ? ReportService::getInstance($w)->getConnection($p["id"]) : new ReportConnection($w);
    $report_connection->fill($_POST);
    if (empty($_POST["s_db_password"])) {
        $report_connection->s_db_password = null;
    }
    $report_connection->insertOrUpdate();
    $w->msg("Connection " . (!empty($p["id"]) ? "updated" : "created"), "/report-connections");
}
