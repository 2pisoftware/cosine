<?php

declare(strict_types=1);

function ajax_get_keys_GET(Web $w)
{
    $w->setLayout(null);

    $params = $w->pathMatch();
    $me = AuthService::getInstance($w)->user();

    // if we're trying to access someone elses keys, enforce admin
    if (!empty($params) && $params[0] != $me->id && !$me->hasRole("admin")) {
        return $w->out((new JsonResponse())->setErrorResponse("Missing permission", []));
    }

    $keys = AuthService::getInstance($w)
        ->getObjects("WebAuthnCredential", [
            "user_id" => $params[0] ?? $me->id,
            'is_deleted' => 0,
        ]);

    return $w->out(
        (new JsonResponse())
            ->setSuccessfulResponse("OK", [
                "keys" => array_map(fn($val) => ([
                    "id" => $val->id,
                    "name" => $val->name,
                    "dt_created" => $val->dt_created,
                ]), $keys)
            ])
    );
}
