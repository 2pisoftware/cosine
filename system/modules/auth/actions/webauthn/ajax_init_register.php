<?php

declare(strict_types=1);

function ajax_init_register_POST(Web $w)
{
    header('Content-Type: application/json');
    $w->setLayout(null);

    if (!Config::get("auth.login.allow_passkey")) {
        $w->out((new JsonResponse())->setErrorResponse("Unavailable on this service", []));
    }

    try {
        $ret = WebAuthnService::getInstance($w)
            ->beginRegistration(AuthService::getInstance($w)->user());
        $w->out($ret);
    } catch (Exception $e) {
        $w->out((new JsonResponse())->setErrorResponse("Failed", []));
    }
}
