<?php
function csv_ALL(Web $w)
{
    $w->setLayout(null);
    //Find class name of insight
    $p = $w->pathMatch('insight_class');
    if (empty($p['insight_class'])) {
        $w->error('No insight class name found', '/insights');
    }
    //find insight that matches class name
    $insight = InsightService::getInstance($w)->getInsightInstance($p['insight_class']);
    if (empty($insight)) {
        $w->error('No insight found for class name', '/insights');
    }

    if (!InsightService::getInstance($w)->canViewInsight(
        AuthService::getInstance($w)->user()->id,
        $p["insight_class"]
    )) {
        return $w->error("You do not have permission to view this insight", "/insights");
    }

    $run_data = $insight->run($w, $_REQUEST);

    InsightService::getInstance($w)->exportcsv($run_data, $p['insight_class']);
}
