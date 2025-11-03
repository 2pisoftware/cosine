<?php

function index_GET(Web $w)
{
    $w->ctx('title', 'Cosine Maintenance');

    // Get server name
    $w->ctx('server', php_uname());
    $load = null;

    // Get load avg if on linux
    if (stristr(PHP_OS, "Linux")) {
        $load = sys_getloadavg();
    }
    
    $w->ctx('load', $load);
    $w->ctx('count_indexed', $w->db->get('object_index')->count());
    $w->ctx('audit_row_count', $w->db->get('audit')->count());
    $w->ctx("number_of_printers", 0);
}
