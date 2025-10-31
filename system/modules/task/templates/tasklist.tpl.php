<style>
    td {
        padding: 5px 20px !important;
    }
</style>

<?php
echo HtmlBootstrap5::filter("Filter Tasks", $filter_data, "/task/tasklist", "GET");

if (!empty($table_data)) {
    echo HtmlBootstrap5::paginatedTable(
        header: $table_header,
        data: $table_data,
        page: $page,
        page_size: $page_size,
        total_results: $total_results,
        base_url: "/task/tasklist",
        sort: $sort,
        sort_direction: $sort_direction,
        page_query_param: 'task__page',
        pagesize_query_param: 'task__page-size',
        sort_query_param: 'task__sort',
        sort_direction_param: 'task__sort-direction',
    );
} else {
    echo '<h3><small>No tasks found.</small></h3>';
}
