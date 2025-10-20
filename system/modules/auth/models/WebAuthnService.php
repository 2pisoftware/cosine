<?php

declare(strict_types=1);

use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Uuid;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

class WebAuthnService extends DbService
{
    private function getRelyingParty()
    {
        return PublicKeyCredentialRpEntity::create(
            Config::get("main.application_name", "Cosine"),
            parse_url(Config::get("auth.passkeys.relyingParty"))["host"]
        );
    }

    private function createUserEntity(User $user)
    {
        return PublicKeyCredentialUserEntity::create(
            $user->login,
            "$user->id",
            $user->getFullName() ?? $user->getContact()->email,
        );
    }

    private function createSerialiser()
    {
        $manager = AttestationStatementSupportManager::create();
        $manager->add(NoneAttestationStatementSupport::create());

        $factory = new WebauthnSerializerFactory($manager);
        $serialiser = $factory->create();

        return $serialiser;
    }

    public function beginRegistration(User $user)
    {
        $rp = $this->getRelyingParty();

        $entity = $this->createUserEntity($user);

        $challenge = random_bytes(16);

        $selectionCriteria = AuthenticatorSelectionCriteria::create(
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
        );

        $pkOptions = PublicKeyCredentialCreationOptions::create(
            $rp,
            $entity,
            $challenge,
            authenticatorSelection: $selectionCriteria,
        );

        $this->w->session("webauthn__options", $pkOptions);
        $this->w->session("webauthn__entity", $entity);

        $serialiser = $this->createSerialiser();

        return $serialiser->serialize(
            $pkOptions,
            "json",
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
            ]
        );
    }

    public function completeRegistration(string $data)
    {
        /**
         * @var PublicKeyCredentialCreationOptions
         */
        $pkOptions = $this->w->session("webauthn__options");
        if (!$pkOptions) {
            throw new Exception("Must beginRegistration first");
        }

        $serialiser = $this->createSerialiser();

        $credential = $serialiser->deserialize($data, PublicKeyCredential::class, "json");

        if (!$credential->response instanceof AuthenticatorAttestationResponse) {
            throw new Exception("Invalid response");
        }

        $csmFactory = $this->getCeremonyFactory();
        $creationCsm = $csmFactory->creationCeremony();

        $host = parse_url(Config::get("auth.passkeys.relyingParty"));

        $validator = AuthenticatorAttestationResponseValidator::create($creationCsm);

        $source = $validator->check(
            $credential->response,
            $pkOptions,
            $host["scheme"] . "://" . $host["host"]
        );

        $credObj = new WebAuthnCredential($this->w);
        $credObj->type = $source->type;
        $credObj->credentialId = base64_encode($source->publicKeyCredentialId);
        $credObj->transports = implode(",", $source->transports);
        $credObj->user_id = $pkOptions->user->id;
        $credObj->aaguid = $source->aaguid;
        $credObj->publicKey = base64_encode($source->credentialPublicKey);
        $credObj->attestationType = $source->attestationType;
        $credObj->counter = 0;
        $credObj->insert();

        $this->w->sessionUnset("webauthn__options");
        $this->w->sessionUnset("webauthn__entity");
    }

    public function beginAuthenticate()
    {
        $requestOptions = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
        );

        $this->w->session("webauthn__options", $requestOptions);

        $serialiser = $this->createSerialiser();

        return $serialiser->serialize(
            $requestOptions,
            "json",
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
            ]
        );
    }

    public function completeAuthenticate(string $data)
    {
        /**
         * @var PublicKeyCredentialRequestOptions
         */
        $requestOptions = $this->w->session("webauthn__options");
        if (!$requestOptions) {
            throw new Exception("Must beginAuthenticate first");
        }

        $serialiser = $this->createSerialiser();

        $credential = $serialiser->deserialize($data, PublicKeyCredential::class, "json");

        if (!$credential->response instanceof AuthenticatorAssertionResponse) {
            throw new Exception("Invalid response");
        }

        $csmFactory = $this->getCeremonyFactory();

        $requestCsm = $csmFactory->requestCeremony();

        $validator = AuthenticatorAssertionResponseValidator::create(
            $requestCsm
        );

        $source = $this->getObject("WebAuthnCredential", [
            "credentialId" => base64_encode($credential->rawId),
        ]);

        if (empty($source)) {
            throw new Exception("Invalid credential");
        }

        $sourceObj = new PublicKeyCredentialSource(
            base64_decode($source->credentialId),
            $source->type,
            explode(",", $source->transports),
            $source->attestationType,
            new EmptyTrustPath(),
            Uuid::fromString($source->aaguid),
            base64_decode($source->publicKey),
            $source->user_id,
            $source->counter,
        );

        $checked = $validator->check(
            $sourceObj,
            $credential->response,
            $requestOptions,
            parse_url(Config::get("auth.passkeys.relyingParty"))["host"],
            $source->user_id
        );

        $source->counter = $checked->counter;
        $source->update();

        $this->w->sessionUnset("webauthn__options");

        return $source->user_id;
    }

    private function getCeremonyFactory()
    {
        $host = parse_url(Config::get("auth.passkeys.relyingParty"));

        $csmFactory = new CeremonyStepManagerFactory();
        $csmFactory->setAllowedOrigins([
            $host["scheme"] . "://" . $host["host"]
        ]);

        return $csmFactory;
    }
}
