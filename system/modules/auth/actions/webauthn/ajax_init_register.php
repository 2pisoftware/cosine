<?php

declare(strict_types=1);

function ajax_init_register_POST(Web $w)
{
    $w->setLayout(null);
    header('Content-Type: application/json');

    try {
        $ret = WebAuthnService::getInstance($w)
            ->beginRegistration(AuthService::getInstance($w)->user());
        $w->out($ret);
    } catch (Exception $e) {
        $w->out((new JsonResponse())->setErrorResponse("Failed", []));
    }
}
