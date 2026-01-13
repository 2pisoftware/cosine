<?php

function printfile_GET(Web $w)
{
    if (empty($_GET["filename"])) {
        $w->out("No filename specified");
    }
    $form = [
        "Print to" => [
            [["Printer", "select", "printer_id", null, PrinterService::getInstance($w)->getPrinters(), "null"]],
            [["Filename", "hidden", "file", $_GET["filename"]]]]
    ];
    
    $w->out(HtmlBootstrap5::multiColForm($form, "/admin/printfile", "POST", "Print", null, null, null, "_self", true, ["printer_id"]));
}

function printfile_POST(Web $w)
{
    $printer = PrinterService::getInstance($w)->getPrinter($_POST["printer_id"]);
    if (empty($printer->id)) {
        $w->out("Printer does not exist");
    }
    
    PrinterService::getInstance($w)->printJob(urldecode($_POST["file"]), $printer);
    $w->msg("File has been sent to the printer", "/admin/printqueue");
}
