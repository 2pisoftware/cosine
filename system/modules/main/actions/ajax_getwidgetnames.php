<?php

function ajax_getwidgetnames_GET(Web $w)
{
    $w->setLayout(null);
    $module = Request::string("source");
    if (!empty($module)) {
        $names = WidgetService::getInstance($w)->getWidgetNamesForModule($module);
        echo json_encode($names);
    }
}
