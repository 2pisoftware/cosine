<?php
// Search Filter: selecting an Module will dynamically load the Category dropdown with available values
function reportAjaxModuletoCategory_ALL(Web $w)
{
    $category = [];
    $module = Request::string('id');

    // organise criteria
    $who = $w->session('user_id');
    $where = [];
    if ($module != "") {
        $where['report.module'] = $module;
    }

    // get report categories from available report list
    $reports = ReportService::getInstance($w)->getReportsbyUserWhere($who, $where);
    if ($reports) {
        foreach ($reports as $report) {
            if (!array_key_exists($report->category, $category)) {
                $category[$report->category] = [$report->getCategoryTitle(), $report->category];
            }
        }
    }
    if (!$category) {
        $category = [["No Reports", ""]];
    }

    // load Category dropdown and return
    $category = HtmlBootstrap5::select("category", $category);

    $w->setLayout(null);
    $w->out(json_encode($category));
}
