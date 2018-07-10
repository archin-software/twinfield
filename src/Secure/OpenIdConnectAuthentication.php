<?php

namespace PhpTwinfield\Secure;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PhpTwinfield\Office;
use PhpTwinfield\Secure\Provider\InvalidAccessTokenException;
use PhpTwinfield\Secure\Provider\OAuthException;
use PhpTwinfield\Secure\Provider\OAuthProvider;

/**
 * This class allows you to authenticate with an access token to the Twinfield APIs.
 *
 * @see OAuthProvider for retrieving an access code.
 */
class OpenIdConnectAuthentication extends AuthenticatedConnection
{
    /**
     * @var OAuthProvider
     */
    private $provider;

    /**
     * @var null|string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $refreshToken;

    /**
     * @var Office
     */
    private $office;

    /**
     * @var string
     */
    private $cluster;

    /**
     * The office code that is part of the Office object that is passed here will be
     * the default office code used during requests. If an office code is included in
     * the SOAP request body, this will always take precedence over this default.
     *
     * Please note that when you leave the office code blank you will have to supply
     * it with every request.
     */
    public function __construct(OAuthProvider $provider, string $refreshToken, ?Office $office)
    {
        $this->provider     = $provider;
        $this->refreshToken = $refreshToken;
        $this->office       = $office;
    }

    protected function getCluster(): ?string
    {
        return $this->cluster;
    }

    protected function getSoapHeaders()
    {
        $headers = [
            "AccessToken"   =>  $this->accessToken,
        ];

        // Watch out. When you don't supply an Office and do an authenticated call you will get an
        // exception from Twinfield saying 'Toegang geweigerd. Company ontbreekt in request header.'
        if ($this->office !== null) {
            $headers["CompanyCode"] = $this->office->getCode();
        }

        return new \SoapHeader(
            'http://www.twinfield.com/',
            'Header',
            $headers
        );
    }

    /**
     * @throws OAuthException
     */
    protected function login(): void
    {
        try {
            $validationResult = $this->validateToken();
        } catch (InvalidAccessTokenException $e) {
            $this->refreshToken();
            $validationResult = $this->validateToken();
        }

        $this->cluster = $validationResult["twf.clusterUrl"];
    }

    /**
     * Validate the OAuth2 access token. If an access token is deemed valid, Twinfield returns a JSON
     * object containing information about the access token. If it is invalid, Twinfield returns a
     * status 400, in which case 'file_get_contents' returns false.
     *
     * @throws OAuthException
     * @throws InvalidAccessTokenException
     */
    protected function validateToken(): array
    {
        $validationUrl    = "https://login.twinfield.com/auth/authentication/connect/accesstokenvalidation?token=";
        $validationResult = false;
        try {
            if ($this->accessToken !== null) {
                $validationResult = @file_get_contents($validationUrl . urlencode($this->accessToken));
            }
        } catch (\Exception $e) {
            throw new OAuthException("Could not validate access token: {$e->getMessage()}");
        }

        if ($validationResult === false) {
            throw new InvalidAccessTokenException("Access token is invalid.");
        }

        $resultDecoded = \json_decode($validationResult, true);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new OAuthException("Error while decoding JSON: " . \json_last_error_msg());
        }
        return $resultDecoded;
    }

    /**
     * @throws OAuthException
     */
    protected function refreshToken(): void
    {
        try {
            $accessToken = $this->provider->getAccessToken(
                "refresh_token",
                ["refresh_token" => $this->refreshToken]
            );
        } catch (IdentityProviderException $e) {
            throw new OAuthException($e->getMessage(), 0, $e);
        }

        $this->accessToken = $accessToken->getToken();
    }
}