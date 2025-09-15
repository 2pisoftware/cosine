<?php

function simple_pagination(Web $w, array $params)
{
	$w->ctx("current", $params["current"]);
	$w->ctx("page_size", $params["page_size"]);
	$w->ctx("total", $params["total"]);
	$w->ctx("param", $params["param"]);
	$w->ctx("base_url", $params["base_url"]);
}
