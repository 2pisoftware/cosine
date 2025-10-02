<?php

declare(strict_types=1);

function ajax_register_POST(Web $w)
{
    $w->setLayout(null);
    header('Content-Type: application/json');

    try {
        WebAuthnService::getInstance($w)->completeRegistration(file_get_contents("php://input"));
    } catch (Exception $e) {
        $w->out((new JsonResponse())->setErrorResponse("Failed", []));
    }

    http_response_code(204);
    exit();
}
