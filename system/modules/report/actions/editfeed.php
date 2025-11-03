<?php
function editfeed_GET(Web &$w)
{
    ReportService::getInstance($w)->navigation($w, "Edit Feed");

    $p = $w->pathMatch("id");

    $feed = ReportService::getInstance($w)->getFeedInfobyId($p["id"]);

    // get list of reports for logged in user. sort to list unapproved reports first
    $reports = ReportService::getInstance($w)->getReportsbyUserId($w->session('user_id'));

    // if i am a member of a list of reports, lets display them
    if (($reports) && (AuthService::getInstance($w)->user()->hasRole("report_editor")  || AuthService::getInstance($w)->user()->hasRole("report_admin"))) {
        foreach ($reports as $report) {
            // get report data
            $rep = ReportService::getInstance($w)->getReportInfo($report->report_id);
            $myrep[] = [$rep->title, $rep->id];
        }
    }

    $note = "Available Formats: html, csv, pdf, xml<br>";
    $note .= "Date Formats must be <b>d/m/Y</b> to mimic date picker";

    $f = HtmlBootstrap5::form([
    ["Create a Feed from a Report","section"],
    ["Select Report","select","rid",$feed->report_id,$myrep],
    ["Feed Title","text","title",$feed->title],
    ["Description","textarea","description",$feed->description,"40","6"],
    ["Feed URL","static","url", $feed->url],
    ["Note","static","url", $note],
    ], $w->localUrl("/report/editfeed/".$feed->id), "POST", " Update ");

    $w->ctx("editfeed", $f);
}

function editfeed_POST(Web &$w)
{
    ReportService::getInstance($w)->navigation($w, "Create a Feed");

    $p = $w->pathMatch("id");

    $feed = ReportService::getInstance($w)->getFeedInfobyId($p["id"]);

    $arr["report_id"] = $_REQUEST["rid"];
    $arr["title"] = $_REQUEST["title"];
    $arr["description"] = $_REQUEST["description"];

    $feed->fill($arr);
    $feed->update();

    $w->msg("Feed " . $feed->title . " has been updated", "/report/listfeed/");
}
