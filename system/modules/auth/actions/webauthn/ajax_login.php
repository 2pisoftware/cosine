<?php

declare(strict_types=1);

function ajax_login_POST(Web $w)
{
    $w->setLayout(null);

    try {
        WebAuthnService::getInstance($w)->completeAuthenticate(file_get_contents("php://input"));
    } catch (Exception $e) {
        return $w->out((new JsonResponse())->setErrorResponse("Failed", []));
    }

    // if the above doesn't throw, we're in

    $w->out((new JsonResponse())->setSuccessfulResponse(
        null,
        [
            "redirect_url" => $w->localUrl(AuthService::getInstance($w)->user()->redirect_url)
        ]
    ));
}
