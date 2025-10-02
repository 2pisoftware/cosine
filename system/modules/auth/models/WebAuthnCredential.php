<?php

declare(strict_types=1);

use Webauthn\PublicKeyCredentialDescriptor;

class WebAuthnCredential extends DbObject
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $credentialId;

    /**
     * @var string
     */
    public $transports;

    /**
     * @var string
     */
    public $user_id;

    /**
     * @var string
     */
    public $attestationType;

    /**
     * uuid
     * @var string
     */
    public $aaguid;

    /**
     * @var string
     */
    public $publicKey;

    public $dt_created;

    public function getPublicKeyCredentialDescriptor()
    {
        return PublicKeyCredentialDescriptor::create(
            $this->type,
            base64_decode($this->credentialId),
            explode(",", $this->transports),
        );
    }
}
