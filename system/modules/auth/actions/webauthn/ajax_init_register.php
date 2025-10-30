<?php

declare(strict_types=1);

function ajax_init_register_POST(Web $w)
{
    header('Content-Type: application/json');
    $w->setLayout(null);

    if (!Config::get("auth.login.allow_passkey")) {
        $w->out((new JsonResponse())->setErrorResponse("Unavailable on this service", []));
    }

    $user = AuthService::getInstance($w)->user();

    $rawBody = file_get_contents("php://input");

    // if we're an admin, we can add passkeys to other users
    if (!empty($rawBody)) {
        $body = json_decode($rawBody, true);
        if (!empty($body["user_id"]) && $user->is_admin) {
            $user = AuthService::getInstance($w)->getUser($body["user_id"]);
        }
    }

    try {
        $ret = WebAuthnService::getInstance($w)
            ->beginRegistration($user);
        $w->out($ret);
    } catch (Exception $e) {
        $w->out((new JsonResponse())->setErrorResponse("Failed", []));
    }
}
