<?php

function admin_ALL(Web $w)
{
    TagService::getInstance($w)->navigation($w, "Tag Admin");

    // Get pagination parameters
    $page = $w->sessionOrRequest("tag__page", 1);
    $page_size = $w->sessionOrRequest("tag__page-size", 20);
    $sort = $w->sessionOrRequest("tag__sort", 'tag.tag');
    $sort_direction = $w->sessionOrRequest("tag__sort-direction", 'asc');

    // Get total count and paginated tags via TagService
    $tagService = TagService::getInstance($w);
    $total_count = $tagService->getTagsCount();
    $tags = $tagService->getTagsPaginated($page, $page_size, $sort, $sort_direction);

    $table_header = [
        ['tag.tag', "Tag"],
        "# Assigned",
        "Actions"
    ];

    $table_data = [];
    if (!empty($tags)) {
        foreach ($tags as $tag) {
            $table_data[] = [
                $tag->tag,
                $tag->countAssignedObjects(),
                HtmlBootstrap5::buttonGroup(
                    HtmlBootstrap5::b("/tag/edit/".$tag->id, "Edit", false, null, false, "btn btn-sm btn-primary").
                    HtmlBootstrap5::b("/tag/delete/".$tag->id, "Delete", "Are you sure you want to delete the {$tag->tag} tag?", null, false, 'btn btn-sm btn-danger')
                )
            ];
        }
    }

    $w->ctx("table_header", $table_header);
    $w->ctx("table_data", $table_data);
    $w->ctx("page", $page);
    $w->ctx("page_size", $page_size);
    $w->ctx("total_results", $total_count);
    $w->ctx("sort", $sort);
    $w->ctx("sort_direction", $sort_direction);
}
