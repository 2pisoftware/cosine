<?php

function ajax_nick_POST(Web $w)
{
    header('Content-Type: application/json');

    $w->setLayout(null);
    [$id] = $w->pathMatch();

    $body = json_decode(file_get_contents("php://input"), true);
    if (empty($body["name"])) {
    }

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

    if (empty($key)) {
        http_response_code(404);
    } else {
        $key->name = $body["name"];
        $key->update();
        http_response_code(204);
    }

    exit();
}
