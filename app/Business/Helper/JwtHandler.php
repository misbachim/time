<?php

namespace App\Business\Helper;

use Carbon\Carbon;
use Emarref\Jwt\Algorithm;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Encryption;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Token;
use Emarref\Jwt\Verification;

/**
 * This is a class for gathering information about API requester
 */
class JwtHandler
{
    public $jwt;
    public $encryption;

    public function __construct()
    {
        $this->jwt = new Jwt();

        $algorithm = new Algorithm\Rs256();
        $this->encryption = Encryption\Factory::create($algorithm);
        $privateKey = file_get_contents(config('app.key_private'));
        $this->encryption->setPrivateKey($privateKey);
        $publicKey = file_get_contents(config('app.key_public'));
        $this->encryption->setPublicKey($publicKey);
    }

    /**
     * Function to get base64 token
     * @param array $pubClaims
     * @param $subject
     * @param array $audiences
     * @param string $issuer
     * @return string
     */
    public function getB64Token($pubClaims = array(), $subject, $audiences=array(), $issuer='')
    {
        $token = new Token();

        $token->addClaim(new Claim\Issuer(empty($issuer)? config('app.domain') : $issuer));
        $token->addClaim(new Claim\Subject($subject));
        $token->addClaim(new Claim\Audience($audiences));
        $token->addClaim(new Claim\Expiration(new Carbon(config('app.token_ttl').' seconds')));
        $token->addClaim(new Claim\NotBefore(Carbon::now()));
        $token->addClaim(new Claim\IssuedAt(Carbon::now()));
        $token->addClaim(new Claim\JwtId($subject.'_'.Carbon::now()->timestamp));

        foreach ($pubClaims as $key => $value) {
            $token->addClaim(new Claim\PublicClaim($key, $value));
        }

        $b64 = $this->jwt->serialize($token, $this->encryption);

        return $b64;
    }

    /**
     * Function to verify token, return payload if valid
     * @param $b64
     * @return Token\Payload
     */
    public function verifyToken($b64)
    {
        $token = $this->jwt->deserialize($b64);
        $payload = $token->getPayload();

        $context = new Verification\Context($this->encryption);
        $context->setAudience($payload->findClaimByName(Claim\Audience::NAME)->getValue());
        $context->setIssuer($payload->findClaimByName(Claim\Issuer::NAME)->getValue());
        $context->setSubject($payload->findClaimByName(Claim\Subject::NAME)->getValue());

        $this->jwt->verify($token, $context);

        return $payload;
    }

    public function renewToken($b64)
    {
        $token = $this->jwt->deserialize($b64);
        $payload = $token->getPayload();
        $payload->findClaimByName(Claim\Expiration::NAME)->setValue(new Carbon(config('app.token_ttl').' seconds'));
        $payload->findClaimByName(Claim\NotBefore::NAME)->setValue(Carbon::now());
        $payload->findClaimByName(Claim\IssuedAt::NAME)->setValue(Carbon::now());
        $payload->findClaimByName(Claim\JwtId::NAME)->setValue($payload->findClaimByName(Claim\Subject::NAME)->getValue().'_'.Carbon::now()->timestamp);

        $b64 = $this->jwt->serialize($token, $this->encryption);

        return $b64;
    }
}
