<?php

function addwidget_GET(Web $w)
{
    list($module) = $w->pathMatch("module");

    $modulelist = $w->modules();
    $modules = array_filter($modulelist, function ($module) use (&$w) {
        $names = WidgetService::getInstance($w)->getWidgetNamesForModule($module);
        return !empty($names);
    });

    $form = ["Add a widget" => [
        [(new Html\Form\Select([
            "label" => "Source module",
            "id|name" => "source_module",
            "options" => $modules
        ]))],
        [(new Html\Form\Select([
            "label" => "Widget Name",
            "id|name" => "widget_name",
            "options" => []
        ]))],
    ]];

    $w->ctx("widgetform", HtmlBootstrap5::multiColForm($form, "/main/addwidget/{$module}", "POST", __("Add")));
}

function addwidget_POST(Web $w)
{
    list($module) = $w->pathMatch("module");

    $widget = new WidgetConfig($w);
    $widget->destination_module = $module;
    $widget->custom_config = "";
    $widget->fill($_POST);
    $widget->user_id = AuthService::getInstance($w)->user()->id;
    $response = $widget->insert();

    if ($response === true) {
        $w->msg(__("Widget Added"), "/{$module}/index");
    } else {
        $w->error(__("Could not add widget"), "/{$module}/index");
    }
}
