<?php

use RobThree\Auth\Providers\Qr\QRServerProvider;
use RobThree\Auth\TwoFactorAuth;

function ajax_get_mfa_qr_code_GET(Web $w)
{
    $w->setLayout(null);

    $user_id = Request::int("id");
    if (empty($user_id)) {
        $w->out((new JsonResponse())->setErrorResponse("Request data missing", null));
        return;
    }

    $user = AuthService::getInstance($w)->getUser($user_id);
    if (empty($user)) {
        $w->out((new JsonResponse())->setErrorResponse("Unable to find user", null));
        return;
    }

    $tfa = AuthService::getInstance($w)->createTfaProvider();

    // TODO: in 3.0, the default secret length increases to 160
    $user->mfa_secret = $tfa->createSecret(bits: 160);

    $qr_code = $tfa->getQRCodeImageAsDataUri(
        label: str_replace(" ", "", $user->getFullName()),
        secret: $user->mfa_secret,
    );

    if (!$user->update()) {
        $w->out((new JsonResponse())->setErrorResponse("Failed to update generate MFA code", null));
        return;
    }

    $w->out((new JsonResponse())->setSuccessfulResponse("User details updated", ["qr_code" => $qr_code, "mfa_secret" => chunk_split($user->mfa_secret, 4, " ")]));
}
