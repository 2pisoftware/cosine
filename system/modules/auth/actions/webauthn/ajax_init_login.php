<?php

declare(strict_types=1);

function ajax_init_login_POST(Web $w)
{
    $w->setLayout(null);
    header('Content-Type: application/json');

    try {
        $options = WebAuthnService::getInstance($w)->beginAuthenticate();
        $w->out($options);
    } catch (Exception $e) {
        $w->out((new JsonResponse())->setErrorResponse("Failed", []));
    }
}
