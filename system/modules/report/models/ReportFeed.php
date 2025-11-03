<?php
class ReportFeed extends DBObject
{
    public $report_id;
    public $title;
    public $description;
    public $report_key;
    public $url;
    public $dt_created;
    public $is_deleted;

    // get feed key upon insert of new feed
    public function insert($force_validation = false): void
    {
        if (!$this->report_key) {
            $this->report_key = uniqid();
        }

        // insert feed into database
        parent::insert();
    }
}
