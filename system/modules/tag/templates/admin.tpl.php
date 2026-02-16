<?php if (!empty($table_data)) {
    echo HtmlBootstrap5::paginatedTable(
        header: $table_header,
        data: $table_data,
        page: $page,
        page_size: $page_size,
        total_results: $total_results,
        base_url: "/tag/admin",
        sort: $sort,
        sort_direction: $sort_direction,
        page_query_param: 'tag__page',
        pagesize_query_param: 'tag__page-size',
        sort_query_param: 'tag__sort',
        sort_direction_param: 'tag__sort-direction',
    );
} else {
    echo '<h3><small>No tags exist.</small></h3>';
}
