<?php

namespace SMIT\SDK\Helpers;

use Exception;
use Psr\Http\Message\ResponseInterface;
use SMIT\SDK\Exceptions\BadRequestException;
use SMIT\SDK\Exceptions\NotFoundException;
use SMIT\SDK\Exceptions\ValidationException;
use SMIT\SDK\Exceptions\UnauthorizedException;

trait HttpRequests
{
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
        $response = $this->client->request($verb, $uri,
            empty($payload) ? [] : ['form_params' => $payload]
        );

        if (!$this->isSuccessFul($response)) {
            return $response;
        }

        $responseBody = (string)$response->getBody();

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

        return (int)substr($response->getStatusCode(), 0, 1) === 2;
    }

//    /**
//     * @param ResponseInterface $response
//     * @throws BadRequestException
//     * @throws NotFoundException
//     * @throws ValidationException
//     */
//    protected function handleRequestError(ResponseInterface $response)
//    {
//        if ($response->getStatusCode() === 422) {
//            throw new ValidationException(json_decode((string)$response->getBody(), true));
//        }
//
//        if ($response->getStatusCode() === 404) {
//            throw new NotFoundException();
//        }
//
//        if ($response->getStatusCode() === 400) {
//            throw new BadRequestException((string)$response->getBody());
//        }
//
//        if ($response->getStatusCode() === 401) {
//            throw new UnauthorizedException((string)$response->getBody());
//        }
//
//        throw new Exception((string)$response->getBody());
//    }
}
