<?php

declare(strict_types=1);

function ajax_remove_key_GET(Web $w)
{
    $w->setLayout(null);

    [$id] = $w->pathMatch();

    $where = [
        "id" => $id,
        "is_deleted" => 0,
    ];

    // if we're not admin, enforce key owner
    $me = AuthService::getInstance($w)->user();
    if (!$me->hasRole("admin")) {
        $where["user_id"] = $me->id;
    }

    $key = AuthService::getInstance($w)
        ->getObject("WebAuthnCredential", $where);

    if (!empty($key)) {
        $key->delete();
    }

    header('Content-Type: application/json');
    http_response_code(204);
    exit();
}
