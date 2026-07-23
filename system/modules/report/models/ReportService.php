<?php

class ReportService extends DbService
{
    private static array $tables;

    /**
     * Get report by id
     *
     * @param int $id
     * @return Report|null
     */
    public function getReport(int $id): Report|null
    {
        return $this->getObject("Report", $id);
    }

    /**
     * Get all reports
     *
     * @return Report[]
     */
    public function getReports(): array
    {
        return $this->getObjects("Report", ["is_deleted" => 0]);
    }

    /**
     * Get report by module and category
     *
     * @param string $module
     * @param string $category
     * @return Report|null
     */
    public function getReportByModuleAndCategory(string $module, string $category): Report|null
    {
        return $this->getObject('Report', ['module' => $module, 'category' => $category, 'is_deleted' => 0]);
    }

    /**
     * Return list of members attached to a report for given report ID
     *
     * @param int $id
     * @return ReportMember[]
     */
    public function getReportMembers(int $id): array
    {
        return $this->getObjects("ReportMember", ["report_id" => $id, "is_deleted" => 0]);
    }

    /**
     * Get report member
     *
     * @param int $id
     * @param int $uid
     * @return ReportMember|null
     */
    public function getReportMember(int $id, int $uid): ReportMember|null
    {
        $conferred = [];
        $conferred[] = $this->getObject("ReportMember", ["report_id" => $id, "user_id" => $uid, "is_deleted" => 0]);
        $groups = AuthService::getInstance($this->w)->getGroups();

        foreach ($groups ?? [] as $group) {
            if (AuthService::getInstance($this->w)->getUser($uid)->inGroup($group)) {
                $report_member = $this->getObject("ReportMember", ["report_id" => $id, "user_id" => $group->id, "is_deleted" => 0]);
                if (!empty($report_member)) {
                    $conferred[] = $report_member;
                }
            }
        }
        return end($conferred);
    }

    /**
     * Helper function to decide whether or not a user has access to a given report
     *
     * @param Report $report
     * @param ReportMember $member
     * @return bool
     */
    public function canUserEditReport(Report $report, ReportMember $member): bool
    {
        // First, is logged in user a system admin
        if (AuthService::getInstance($this->w)->user()->is_admin == 1) {
            return true;
        }

        // Check if logged in user is report_admin
        if (AuthService::getInstance($this->w)->user()->hasRole("report_admin")) {
            return true;
        }

        if (empty($report->id) || empty($member->id)) {
            return false;
        }

        // Then check if the user has report_editor role
        if (!AuthService::getInstance($this->w)->user()->hasRole("report_editor")) {
            return false;
        }

        // Check that the member given is for the given report
        if ($report->id !== $member->report_id) {
            // Log this event
            LogService::getInstance($this->w)->error("Wrong member given for report (In ReportService, line: " . __LINE__ . ")");
            return false;
        }

        // User is report_editor, check if this report is theirs or that they have edit access
        if ($member->role === "OWNER" or $member->role === "EDITOR") {
            return true;
        }

        return false;
    }

    /**
     * Returns array of connection objects
     *
     * @return array connections
     */
    public function getConnections()
    {
        return $this->getObjects("ReportConnection", ["is_deleted" => "0"]);
    }

    /**
     * Get report connection by id
     *
     * @param int $id
     * @return ReportConnection|null
     */
    public function getConnection(int $id): ReportConnection|null
    {
        return $this->getObject("ReportConnection", ["id" => $id, "is_deleted" => "0"]);
    }

    // function to sort lists by date schedule
    /**
     * Function to sort lists by date schedule
     *
     * @param mixed $a
     * @param mixed $b
     * @return int
     */
    public static function sortBySchedule(mixed $a, mixed $b): int
    {
        if ($a->dt_schedule == $b->dt_schedule) {
            return 0;
        }
        return ($a->dt_schedule < $b->dt_schedule) ? +1 : -1;
    }

    /**
     * get list of modules for HtmlBootstrap5::select
     */
    public function getModules()
    {
        $modules = $this->w->modules();
        $parsed_modules = [];
        foreach ($modules ?? [] as $module) {
            $parsed_modules[] = [ucfirst($module), $module];
        }
        sort($parsed_modules);
        return $parsed_modules;
    }

    /**
     * static list of group permissions
     *
     * @return array
     */
    public function getReportPermissions(): array
    {
        return ["USER", "EDITOR"];
    }

    /**
     * Return a report given its ID
     *
     * @param int $id
     * @return Report|null
     */
    public function getReportInfo($id): Report|null
    {
        return $this->getReport($id);
    }

    /**
     * Return list of feeds
     *
     * @return ReportFeed[]
     */
    public function getFeeds(): array
    {
        return $this->getObjects("ReportFeed", ["is_deleted" => 0]);
    }

    /**
     * Return a feed given its id
     *
     * @param int $id
     * @return ReportFeed|null
     */
    public function getFeedInfobyId(int $id): ReportFeed|null
    {
        return $this->getObject("ReportFeed", ["id" => $id, "is_deleted" => 0]);
    }

    /**
     * Return a feed given its report id
     *
     * @param int $id
     *
     */
    public function getFeedInfobyReportId(int $id)
    {
        return $this->getObject("ReportFeed", ["report_id" => $id, "is_deleted" => 0]);
    }

    /**
     * Return a feed given its key
     *
     * @param string $key
     * @return ReportFeed|null
     */
    public function getFeedInfobyKey(string $key): ReportFeed|null
    {
        return $this->getObject("ReportFeed", ["report_key" => $key, "is_deleted" => 0]);
    }

    /**
     * Return list of APPROVED and NOT DELETED report IDs for a given a user ID and a where clause
     *
     * @param int $id
     * @param string $where
     * @return Report[]
     */
    public function getReportsbyUserWhere(int $id, string $where): array
    {
        // Clause for admin user
        if (AuthService::getInstance($this->w)->user()->hasRole("report_admin")) {
            return $this->getReports();
        }

        // need to get reports for me and my groups
        // me
        $myid = [$this->_db->quote($id)];

        // need to check all groups given group member could be a group
        $groups = AuthService::getInstance($this->w)->getGroups();

        foreach ($groups ?? [] as $group) {
            if (AuthService::getInstance($this->w)->user()->inGroup($group)) {
                $myid[$group->id] = $this->_db->quote($group->id);
            }
        }

        // list of IDs to check for report membership, my ID and my group IDs
        $theid = implode(",", $myid);

        $filter = $this->unitaryWhereToAndClause($where);

        $rows = $this->_db->sql("SELECT distinct r.* from " . ReportMember::$_db_table . " as m inner join " .
            Report::$_db_table . " as r on m.report_id = r.id " .
            " where m.user_id in (" . $theid . ") " . $filter .
            " and r.is_deleted = 0 and m.is_deleted = 0 " .
            " order by r.is_approved desc,r.title")->fetchAll();
        return $this->fillObjects("Report", $rows);
    }

    /**
     * Unitary approach to form an 'and' clause for 'where' from text or key values
     *
     * @param string $where
     * @param array $where
     * @return string
     */
    public function unitaryWhereToAndClause($where)
    {
        // adapt if we were given raw SQL!
        if (!is_array($where)) {
            // assume we only check a single equality/pair
            $spec = explode("=", $where);
            // anything else will be turned to mush
            $column = explode(" ", trim($spec[0]));
            $column = explode(".", end($column));
            $match = trim(end($spec));
            $match = str_replace("'", "", $match);
            $where = [
                end($column) => $match
            ];
        }
        $filter = "";
        // enforce literal quoted match as r.[columnName] = 'something'
        foreach ($where as $term => $check) {
            if (!empty($check)) {
                $tmp = explode(".", $term);
                $term = trim(end($tmp));
                $tmp = explode(" ", $term);
                $term = trim(end($tmp));
                $check = str_replace("'", "", $check);
                $term = str_replace("'", "", $term);
                $term = str_replace("--", "", $term);
                $term = str_replace(";", "", $term);
                $filter .= " and r." . $term . " = " . $this->_db->quote($check) . " ";
            }
        }
        return $filter;
    }

    /**
     * Return list of APPROVED and NOT DELETED report IDs for a given a user ID as member
     *
     * @param int $id
     * @return ReportMember[]
     */
    public function getReportsbyUserId(int $id): array
    {
        // need to get reports for me and my groups
        // me
        $myid[] = $id;

        // need to check all groups given group member could be a group
        $groups = AuthService::getInstance($this->w)->getGroups();

        if ($groups) {
            foreach ($groups as $group) {
                if (AuthService::getInstance($this->w)->user()->inGroup($group)) {
                    $myid[$group->id] = $group->id;
                }
            }
        }
        // list of IDs to check for report membership, my ID and my group IDs
        //        $id = implode(",", $myid);
        $results = $this->_db->get("report_member")->select("report.*")
            ->leftJoin("report on report_member.report_id = report.id")
            ->where("report_member.user_id", $myid)
            ->where("report.is_deleted", 0)->where("report_member.is_deleted", 0)
            ->orderBy("report.is_approved desc, report.title")->fetchAll();
        return $this->fillObjects("ReportMember", $results);
    }

    /**
     * Return list of APPROVED and NOT DELETED report IDs for a given a user ID and Module
     *
     * @return Report[]
     */
    public function getReportsbyModuleId(): array
    {
        // need to get reports for me and my groups
        // me
        $myid[] = $this->w->session('user_id');

        // need to check all groups given group member could be a group
        $groups = AuthService::getInstance($this->w)->getGroups();

        if ($groups) {
            foreach ($groups as $group) {
                $flg = AuthService::getInstance($this->w)->user()->inGroup($group);
                if ($flg) {
                    $myid[$group->id] = $group->id;
                }
            }
        }
        // list of IDs to check for report membership, my ID and my group IDs
        $id = implode(",", $myid);
        $module = $this->w->currentModule();

        $results = $this->_db->get("report_member")->select("report.*")
            ->leftJoin("report on report_member.report_id = report.id")
            ->where("report_member.user_id", $myid)->where("report.module", $module)
            ->where("report.is_deleted", 0)->where("report_member.is_deleted", 0)
            ->orderBy("report.is_approved desc, report.title")->fetchAll();

        return $this->fillObjects("Report", $results);
    }

    /**
     * Return menu links of APPROVED and NOT DELETED report IDs for a given a user ID as member
     *
     * @return array
     */
    public function getReportsforNav(): array
    {
        $repts = [];
        $reports = $this->getReportsbyModuleId();

        if ($reports) {
            foreach ($reports as $report) {
                $this->w->menuLink("report/runreport/" . $report->id, $report->title, $repts);
            }
        }
        return $repts;
    }

    /**
     * Return a users full name given their user ID
     *
     * @param int $id
     * @return string
     */
    public function getUserById($id): string
    {
        $u = AuthService::getInstance($this->w)->getUser($id);
        return $u ? $u->getFullName() : "";
    }

    /**
     * For parameter dropdowns, run SQL statement and return an array(value,title) for display
     * DANGEROUS
     *
     * @param string $sql
     * @param DbPDO|PDO $connection
     * @return array
     */
    public function getFormDatafromSQL($sql, DbPDO|PDO $connection): array
    {
        $rows = $connection->query(trim($sql))->fetchAll();

        $arr = [];
        if ($rows) {
            foreach ($rows as $row) {
                $arr[] = [$row['title'], $row['value']];
            }
        }
        return $arr;
    }

    /**
     * Given a report SQL statement, return recordset
     * DANGEROUS
     *
     * @param string $sql
     * @param null|DbPDO|PDO $connection
     * @return bool
     */
    public function getExefromSQL(string $sql, null|DbPDO|PDO $connection = null): bool
    {
        return $connection->query($sql)->execute();
    }

    /**
     * Convert dd/mm/yyyy date to yyy-mm-dd for SQL statements
     *
     * @param string $date
     * @return string
     */
    public function date2db(string $date): string
    {
        if ($date) {
            list($d, $m, $y) = preg_split("/\/|-|\./", $date);
            return $y . "-" . $m . "-" . $d;
        }
        return '';
    }

    /**
     * Return all tables in the DB for display
     *
     * @return array
     */
    public function getAllDBTables(): array
    {
        $dbtbl = [];
        foreach ($this->_db->_query("show tables")->fetchAll(PDO::FETCH_NUM) as $table) {
            $dbtbl[] = $table[0];
        }
        ReportService::$tables = $dbtbl;

        return $dbtbl;
    }

    /**
     * Return array of fields/type in a given table
     *
     * @param string $table
     * @return string
     */
    public function getFieldsinTable(string $table): string
    {
        $output = "";

        if (empty(ReportService::$tables)) {
            $this->getAllDBTables();
        }

        // Check that the table actually exists, reduces chance for SQL injection
        if (!in_array(strtolower($table), ReportService::$tables)) {
            return "";
        }

        if ($table != "") {
            $fields = $this->_db->sql("show columns in " . $table)->fetchAll();

            if ($fields) {
                $output = "<table><tr><td><b>Field</b></td><td><b>Type</b></td></tr>";
                foreach ($fields as $field) {
                    $output .= "<tr><td>" . $field['Field'] . "</td><td>" . $field['Type'] . "</td></tr>";
                }
                $output .= "</table>";
            }
        }

        return $output;
    }

    /**
     * Get SQL Statement Type
     *
     * @param string $report_code
     * @return string
     */
    public function getSQLStatementType(string $report_code): string
    {
        // return our list of SQL statements
        preg_match_all("/@@.*?@@/", preg_replace("/\n/", " ", $report_code), $arrsql);

        // if we have statements, continue ...
        $action = "";
        if ($arrsql) {
            foreach ($arrsql as $sql) {
                if ($sql) {
                    foreach ($sql as $s) {
                        list($title, $sql) = preg_split("/\|\|/", $s);
                        // put on one line just to be sure
                        $sql = preg_replace("/\n/", " ", trim($sql));
                        $arr = preg_split("/\s/", $sql);
                        $action .= $arr[0] . ", ";
                    }
                }
            }
            return rtrim($action, ", ");
        } else {
            return "No action Found";
        }
    }

    /**
     * Create an array of available report output formats for inclusion in the parameters form
     *
     * @return array
     */
    public function selectReportFormat(): array
    {
        $arr = [
            ["Web Page", "html"],
            ["Comma Delimited File", "csv"],
            ["PDF File", "pdf"],
            ["XML", "xml"],
        ];

        return [["Format", "select", "format", null, $arr]];
    }

    /**
     * Export a recordset as CSV
     *
     * @param array $rows
     * @param string $title
     */
    public function exportcsv($rows, $title): void
    {
        // set filename
        $filename = str_replace(" ", "_", $title) . "_" . date("Y.m.d-H.i") . ".csv";

        // if we have records, comma delimit the fields/columns and carriage return delimit the rows
        if (!empty($rows)) {
            foreach ($rows as $row) {
                //throw away the first line which list the form parameters
                $crumbs = array_shift($row);
                $title = array_shift($row);
                $hds = array_shift($row);
                $hvals = array_values($hds);

                // find key of any links
                foreach ($hvals as $h) {
                    if (stripos($h, "_link")) {
                        list($fld, $lnk) = preg_split("/_/", $h);
                        $ukey[] = array_search($h, $hvals);
                        unset($hds[$h]);
                    }
                }

                // iterate row to build URL. if required
                if (!empty($ukey)) {
                    foreach ($row as $r) {
                        foreach ($ukey as $n => $u) {
                            // dump the URL related fields for display
                            unset($r[$u]);
                        }
                        $arr[] = $r;
                    }
                    $row = $arr;
                    unset($arr);
                }

                $csv = new ParseCsv\Csv();
                $csv->output_filename = $filename;
                // ignore lib wrapper csv->output, to keep control over header re-sends!
                $this->w->out($csv->unparse($row, $hds));
                // can't use this way without commenting out header section, which composer won't like
                // $this->w->out($csv->output($filename, $row, $hds));
                unset($ukey);
            }
            $this->w->sendHeader("Content-type", "application/csv");
            $this->w->sendHeader("Content-Disposition", "attachment; filename=" . $filename);
            $this->w->setLayout(null);
        }
    }

    /**
     * Export a recordset as PDF
     *
     * @param array $rows
     * @param string $title
     * @param ReportTemplate|null $report_template
     */
    public function exportpdf($rows, $title, ReportTemplate|null $report_template = null): void
    {
        $filename = str_replace(" ", "_", $title) . "_" . date("Y.m.d-H.i") . ".pdf";

        // using TCPDF, but sourcing from Composer
        //require_once('tcpdf/tcpdf.php');

        // instantiate and set parameters
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle($title);
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        //$pdf->setLanguageArray($l);
        // no header, set font and create a page
        $pdf->setPrintHeader(false);
        $pdf->SetFont("helvetica", "B", 9);
        $pdf->AddPage();

        // title of report
        $hd = "<h1>" . $title . "</h1>";
        $pdf->writeHTMLCell(0, 10, 60, 15, $hd, 0, 1, 0, true);
        $created = date("d/m/Y g:i a");
        $pdf->writeHTMLCell(0, 10, 60, 25, $created, 0, 1, 0, true);

        // display recordset

        if (!empty($rows)) {
            if (empty($report_template)) {
                foreach ($rows as $row) {
                    //throw away the first line which list the form parameters
                    $crumbs = array_shift($row);
                    $title = array_shift($row);
                    $hds = array_shift($row);
                    $hds = array_values($hds);

                    $results = "<h3>" . $title . "</h3>";
                    $results .= "<table cellpadding=2 cellspacing=2 border=0 width=100%>\n";
                    foreach ($row as $r) {
                        $i = 0;
                        foreach ($r as $field) {
                            if (!stripos($hds[$i], "_link")) {
                                $results .= "<tr><td width=20%>" . $hds[$i] . "</td><td>" . $field . "</td></tr>\n";
                            }
                            $i++;
                        }
                        $results .= "<tr><td colspan=2><hr /></td></tr>\n";
                    }
                    $results .= "</table><p>";
                    $pdf->writeHTML($results, true, false, true, false);
                }
            } else {
                $templatedata = [];
                foreach ($rows as $row) {
                    $crumbs = array_shift($row);
                    $title = array_shift($row);
                    $hds = array_shift($row);
                    $hds = array_values($hds);

                    $templatedata[] = ["title" => $title, "headers" => $hds, "results" => $row];
                }

                if (!empty($report_template) && !empty($templatedata)) {
                    $results = TemplateService::getInstance($this->w)->render(
                        $report_template->template_id,
                        ["data" => $templatedata, "w" => $this->w, "POST" => $_POST]
                    );

                    $pdf->writeHTML($results, true, false, true, false);
                }
            }
        }

        // set for 'open/save as...' dialog
        $pdf->Output($filename, 'D');
    }

    /**
     * Export a recordset as XML
     *
     * @param array $rows
     * @param string $title
     */
    public function exportxml($rows, $title): void
    {
        $filename = str_replace(" ", "_", $title) . "_" . date("Y.m.d-H.i") . ".xml";

        $this->w->out("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
        $this->w->out("<report>\n");
        $this->w->out("\t<title>" . $title . "</title>\n");
        $this->w->out("\t<created>" . date("d/m/Y h:i:s") . "</created>\n");

        // if we have records ...
        if (!empty($rows)) {
            foreach ($rows as $row) {
                //throw away the first line which list the form parameters
                $crumbs = array_shift($row);
                $title = array_shift($row);
                $hds = array_shift($row);
                $hds = array_values($hds);

                $this->w->out("\t<rows title=\"" . $title . "\">\n");

                foreach ($row as $r) {
                    $this->w->out("\t\t<row>\n");
                    $i = 0;
                    foreach ($r as $field) {
                        if (!stripos($hds[$i], "_link")) {
                            $this->w->out("\t\t\t<" . preg_replace("/\s+/", "", $hds[$i]) . ">" . htmlentities($field) . "</" . preg_replace("/\s+/", "", $hds[$i]) . ">\n");
                        }
                        $i++;
                    }
                    $this->w->out("\t\t</row>\n");
                }
                $this->w->out("\t</rows>\n");
            }
        }
        $this->w->out("</report>\n");

        // set header for 'open/save as...' dialog
        $this->w->sendHeader("Content-type", "application/xml");
        $this->w->sendHeader("Content-Disposition", "attachment; filename=" . $filename);
        $this->w->setLayout(null);
    }

    /**
     * Function to substitute special terms
     *
     * @param string $sql
     * @return array|string
     */
    public function putSpecialSQL(string $sql): array|string
    {
        if ("" == $sql) {
            return '';
        }
        $special = [];
        $replace = [];

        // get user roles
        $usr = AuthService::getInstance($this->w)->user();
        $roles = '';
        if (!empty($usr)) {
            foreach ($usr->getRoles() as $role) {
                $roles .= "'" . $role . "',";
            }
            $roles = rtrim($roles, ",");
        }

        // $special must be in terms of a regexp for preg_match
        $special[0] = "/\{\{current_user_id\}\}/";
        $replace[0] = $_SESSION["user_id"];
        $special[1] = "/\{\{roles\}\}/";
        $replace[1] = $roles;
        $special[2] = "/\{\{webroot\}\}/";
        $replace[2] = $this->w->localUrl();

        // replace and return
        return preg_replace($special, $replace, $sql);
    }

    //
    /**
     * Function to check syntax of report SQL statement
     *
     * @param string $sql
     * @param PDO $connection
     * @return bool
     */
    public function getcheckSQL($sql, PDO $connection): bool
    {
        // checking for rows will return false if no data is returned, even if SQL is ok
        // so let's just run the statement and try to catch any exceptions otherwise SQL runs ok
        try {
            $connection->beginTransaction();
            $rows = $connection->query($sql)->execute();
            $connection->rollBack();
            return true;
        } catch (Exception $e) {
            $connection->rollBack();
            LogService::getInstance($this->w)->error($e->getMessage());
            return false;
        }
    }

    /**
     * Get report template by ID
     *
     * @param int $id
     * @return ReportTemplate|null
     */
    public function getReportTemplate(int $id): ReportTemplate|null
    {
        return $this->getObject("ReportTemplate", $id);
    }

    //
    /**
     * Build the Report navigation
     *
     * @param Web $w
     * @param string|null $title
     * @param array|null $nav
     * @return array
     */
    public function navigation(Web $w, $title = null, $nav = null): array
    {
        if (!empty($title)) {
            $w->ctx("title", $title);
        }

        $nav = $nav ? $nav : [];

        if (AuthService::getInstance($w)->loggedIn()) {
            $w->menuLink("report/index", "Report Dashboard", $nav);

            if (AuthService::getInstance($w)->user()->hasRole("report_editor") || AuthService::getInstance($w)->user()->hasRole("report_admin")) {
                $w->menuLink("report/edit", "Create a Report", $nav);
                $w->menuLink("report-connections", "Connections", $nav);
                $w->menuLink("report/listfeed", "Feeds Dashboard", $nav);
            }
        }

        $w->ctx("navigation", $nav);
        return $nav;
    }

    /**
     * Nav list
     *
     * @return array
     */
    public function navList(): array
    {
        $list = [
            new MenuLinkStruct("Report Dashboard", "report/index")
        ];
        if (AuthService::getInstance($this->w)->user()->hasAnyRole(["report_editor", "report_admin"])) {
            $list = [
                ...$list,
                new MenuLinkStruct("Create a Report", "report/edit"),
                new MenuLinkStruct("Connections", "report-connections"),
                new MenuLinkStruct("Feeds Dashboard", "report/listfeed"),
            ];
        }
        return $list;
    }
}
