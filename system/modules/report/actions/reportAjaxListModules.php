<?php
// Search Filter: load relevnt Module dropdown available values
function reportAjaxListModules_ALL(Web $w)
{
    $modules = [];

    // organise criteria
    $who = $w->session('user_id');
    $where = "";

    // get report categories from available report list
    $reports = ReportService::getInstance($w)->getReportsbyUserWhere($who, $where);
    if ($reports) {
        foreach ($reports as $report) {
            if (!array_key_exists($report->module, $modules)) {
                $modules[$report->module] = [ucfirst($report->module),$report->module];
            }
        }
    }
    if (!$modules) {
        $modules = [["No Reports",""]];
    }

    // load Module dropdown and return
    $modules = HtmlBootstrap5::select("module", $modules);

    $w->setLayout(null);
    $w->out(json_encode($modules));
}
