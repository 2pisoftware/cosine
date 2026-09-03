<?php

/**@author Alice Hutley <alice@2pisoftware.com> */

abstract class InsightBaseClass
{
    public $name;
    public $module;
    public $description;

    abstract public function getFilters(Web $w, array $params = []): array;

    /**
     * runs the insight report. The returned array() should have the following structure:
     * [
     *  [ "title" -> "The title of a sub report",
     *    "header" -> ["Column Header 1", "Column Header 2", ..],
     *    "data" -> [
     *          ["column data 1", "column data 2", ..], // row 1
     *          ["column data 1", "column data 2", ..], // row 2
     *      ]
     *  ],
     *  [...] // more sub reports
     * ]
     *
     * @return InsightReportInterface[]
     */
    abstract public function run(Web $w, array $params = []): array;

    /**
     * Run the insight and return raw data to be exported as CSV/PDF
     * This function MUST return data if 'run' does.
     */
    public function raw(Web $w, array $params = []): array
    {
        return [];
    }

    public function getMembers(Web $w)
    {
        return InsightService::getInstance($w)->getAllMembersForInsightClass(get_class($this));
    }
}
