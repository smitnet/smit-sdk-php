<?php

namespace SMIT\SDK\Helpers;

use SMIT\SDK\Exceptions\ValidationException;
use SMIT\SDK\Exceptions\NotFoundException;
use SMIT\SDK\Exceptions\BadRequestException;
use GuzzleHttp\Client;

trait HttpRequests
{
    /**
     * @var
     */
    private $guzzle;

    /**
     * @var array
     */
    private $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    /**
     * @var array
     */
    private $options = [
        'http_errors' => false,
        'verify' => false,
        'idn_conversion' => false,
    ];

    /**
     * @param string|null $baseUri
     * @param array $auth
     * @return Client
     */
    protected function guzzle(string $baseUri = null, array $auth = [])
    {
        if (array_key_exists('token_type', $auth) && array_key_exists('access_token', $auth)) {
            $this->headers['Authorization'] = sprintf(
                '%s %s',
                $auth['token_type'],
                $auth['access_token']
            );
        }

        if (!is_null($baseUri)) {
            $this->options['base_uri'] = $baseUri;
        }

        $this->guzzle = new Client(array_merge($this->options, [
            'headers' => $this->headers,
        ]));

        return $this->guzzle;
    }

    /**
     * @param string $uri
     *
     * @return mixed
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws ValidationException
     */
    protected function get(string $uri)
    {
        return $this->request('GET', $uri);
    }

    /**
     * @param string $uri
     * @param array $payload
     *
     * @return mixed
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws ValidationException
     */
    protected function post(string $uri, array $payload = [])
    {
        return $this->request('POST', $uri, $payload);
    }

    /**
     * @param string $uri
     * @param array $payload
     *
     * @return mixed
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws ValidationException
     */
    protected function put(string $uri, array $payload = [])
    {
        return $this->request('PUT', $uri, $payload);
    }

    /**
     * @param string $uri
     * @param array $payload
     *
     * @return mixed
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws ValidationException
     */
    protected function delete(string $uri, array $payload = [])
    {
        return $this->request('DELETE', $uri, $payload);
    }

    /**
     * @param string $verb
     * @param string $uri
     * @param array $payload
     *
     * @return mixed
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws ValidationException
     */
    protected function request(string $verb, string $uri, array $payload = [])
    {
        $response = $this->guzzle->request(
            $verb,
            $uri,
            empty($payload) ? [] : ['form_params' => $payload]
        );

        if (!$this->isSuccessFul($response)) {
            return $response;
        }

        $responseBody = (string) $response->getBody();

        return json_decode($responseBody, true) ?: $responseBody;
    }

    /**
     * @param $response
     * @return bool
     */
    public function isSuccessFul($response): bool
    {
        if (!$response) {
            return false;
        }

        return (int) substr($response->getStatusCode(), 0, 1) === 2;
    }
}
