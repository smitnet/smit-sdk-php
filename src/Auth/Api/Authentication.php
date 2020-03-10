<?php

namespace SMIT\SDK\Auth\Api;

use SMIT\SDK\Helpers\HttpRequests;
use SMIT\SDK\Helpers\HttpRedirects;
use SMIT\SDK\Exceptions\ValidationException;
use InvalidArgumentException;

class Authentication
{
    use HttpRequests, HttpRedirects;

    private $domain;
    private $client_id;
    private $client_secret;
    private $scopes = [];

    public function __construct(
        string $domain,
        string $client_id,
        ?string $client_secret = null,
        ?array $scopes = []
    ) {
        $this->domain = $domain;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->scopes = $scopes;
    }

    public static function refresh(string $refreshToken)
    {
        if (empty($refreshToken)) {
            throw new InvalidArgumentException('Refresh token is mandatory');
        } elseif (empty($this->client_id)) {
            throw new InvalidArgumentException('Client id is mandatory');
        } elseif (empty($this->client_secret)) {
            throw new InvalidArgumentException('Client secret is mandatory');
        }

        $endpoint = sprintf('https://%s/token', $this->domain);

        // refresh_token=$refreshToken
        // grant_type=refresh_token

        return $this->guzzle($endpoint);
    }

    /**
     *
     * @param array $options
     * @return exit
     * @throws InvalidArgumentException
     */
    public static function logout(array $options = [])
    {
        if (empty($this->client_id)) {
            throw new InvalidArgumentException('Client id is mandatory');
        }

        $endpoint = sprintf('https://%s/logout', $this->domain);

        return $this->redirect($endpoint, array_merge([
            'client_id' => $this->client_id,
        ], $options));
    }

    /**
     * Federated logout results in logout upon all authenticated
     * applications/websites for the given identity.
     *
     * @param array $options
     * @return SMIT\SDK\Auth\Api\exit
     * @throws InvalidArgumentException
     */
    public static function federatedLogout(array $options = [])
    {
        return $this->logout(array_merge($options, [
            'federated' => true,
        ]));
    }

    public static function login()
    {
        // redirect user to authorization screen for authentication
    }

    public static function exchange()
    {
        // exchange authorization_code with tokens
    }

    public static function verify()
    {
        // verify authenticated user with central platform
    }

    public static function user()
    {
        // fetch current user profile based upon scopes
    }

    public static function scopes()
    {
        // fetch all available scopes for given platform
    }
}
