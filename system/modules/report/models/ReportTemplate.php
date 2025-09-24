<?php

class ReportTemplate extends DbObject
{
    public $report_id;
    public $template_id;
    public $is_email_template;
    public $type;

    public function getReportTypes()
    {
        return ["HTML", "CSV", "XML", "PDF", "Image"];
    }

    public function getReport()
    {
        return $this->getObject("Report", ["id" => $this->report_id, "is_deleted" => 0]);
    }

    public function getTemplate()
    {
        return $this->getObject("Template", ["id" => $this->template_id, "is_deleted" => 0]);
    }
}
