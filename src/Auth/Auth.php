<?php

namespace SMIT\SDK\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SMIT\SDK\Auth\Helpers\PreserveState;
use SMIT\SDK\Auth\Models\ApplicationModel;
use SMIT\SDK\Auth\Models\UserModel;
use SMIT\SDK\Auth\Stores\SessionStore;
use SMIT\SDK\Auth\Stores\StoreInterface;
use SMIT\SDK\Exceptions\UnauthorizedException;
use SMIT\SDK\Exceptions\UnauthorizedScopeException;
use SMIT\SDK\Helpers\HttpRedirects;
use SMIT\SDK\Helpers\HttpRequests;

class Auth
{
    use HttpRequests, HttpRedirects, PreserveState;

    /**
     * @var int
     */
    private $rateLimit = 60;

    /**
     * @var int
     */
    private $rateLimitRemainder = 60;

    /**
     * @param \GuzzleHttp\Client $client
     */
    protected $client;

    /**
     * Storage engine for persistence.
     *
     * @var StoreInterface
     */
    protected $store;

    /**
     * @var array $config
     */
    private $config = [
        'version' => '1.0',
        'response_type' => 'code',
        'response_mode' => 'query',
        'persist_user' => true,
    ];

    /**
     * Default route mappings.
     *
     * @var array
     */
    protected $routeMappings = [
        'api' => 'https://{{domain}}/api/{{version}}',
        'authorize' => 'https://{{domain}}/authorize',
        'logout' => 'https://{{domain}}/api/{{version}}/logout',
        'token' => 'https://{{domain}}/token',
        'user_info' => 'https://{{domain}}/api/{{version}}/me',
        'refresh' => 'https://{{domain}}/token',
        'scopes' => 'https://{{domain}}/api/{{version}}/scopes',
    ];

    /**
     * Default persist mappings.
     *
     * @var array
     */
    protected $persistMappings = [
        'access_token' => 'access_token',
        'refresh_token' => 'refresh_token',
        'expires_at' => 'expires_at',
        'token_type' => 'token_type',
        'user_info' => 'user_info',
        'scopes' => 'scopes',
    ];

    /**
     * Auth constructor.
     *
     * @param array $config
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function __construct(array $config, StoreInterface $store = null)
    {
        $this->setConfig($config)
            ->setStore($store)
            ->formatRouteMappings();
    }

    private function getExceptionHandler(\Exception $exception = null)
    {
        if (!is_null($exception)) {
            switch (get_class($exception)) {
                case UnauthorizedScopeException::class:
                    $message = $exception->getMessage();
                    break;
                case \Exception::class:
                    $message = 'Internal Server Error';
                    break;
            }

            http_response_code(400);

            header('Content-Type: application/json');

            exit(json_encode(array_merge([
                'status_code' => 400,
            ], compact('message'))));
        }
    }

    /**
     * Format route mappings with correct values.
     *
     * @return $this
     */
    private function formatRouteMappings()
    {
        $this->routeMappings = array_map(function ($mapping) {
            $mapping = str_replace('{{domain}}', $this->config['domain'], $mapping);
            $mapping = str_replace('{{version}}', $this->config['version'], $mapping);

            return $mapping;
        }, $this->routeMappings);

        return $this;
    }

    /**
     * Get config by key. Defaults to all configurations.
     *
     * @param string|null $key
     * @return array|mixed
     */
    public function config(string $key = null)
    {
        return array_key_exists($key, $this->config) && !is_null($key)
            ? $this->config[$key] : $this->config;
    }

    /**
     * Get route mapping by key. Defaults to all route mappings.
     *
     * @param string|null $key
     * @return array|mixed
     */
    public function route(string $key = null)
    {
        return array_key_exists($key, $this->routeMappings) && !is_null($key)
            ? $this->routeMappings[$key] : $this->routeMappings;
    }

    /**
     * Set configuration lines whilst merging with current.
     *
     * @param array $config
     * @param bool $replace false
     * @return $this
     * @todo add validation parameters for configuration.
     *
     */
    private function setConfig(array $config)
    {
        foreach (['domain', 'client_id', 'client_secret', 'redirect_uri'] as $required) {
            if (!array_key_exists($required, $config)) {
                throw new \InvalidArgumentException(
                    sprintf('Missing required argument "%s"', $required)
                );
            }
        }

        $this->config = array_merge($this->config, $config);

        return $this;
    }

    public function setStore(StoreInterface $store = null)
    {
        $this->store = !is_null($store)
            ? $store : new SessionStore();

        return $this;
    }

    /**
     * @param string|null $code
     * @return string
     */
    protected function getAuthorizationCode(string $code = null)
    {
        try {
            if ($this->config('response_mode') === 'query' && isset($_GET['code'])) {
                $code = $_GET['code'];
            } else if ($this->config('response_mode') === 'form_post' && isset($_POST['code'])) {
                $code = $_POST['code'];
            }

            if (isset($_GET['error'])) {
                switch ($_GET['error'])
                {
                    case 'invalid_scope':
                        throw new UnauthorizedScopeException(sprintf(
                            'Requested scope(s) don\'t exist: %s', implode(', ',
                                array_diff($this->scopes(), $this->getExternalAuthorizationScopes())
                            )
                        ));
                }

                throw new \Exception('Internal Server Error');
            }

            return $code;
        } catch (\Exception $exception) {
            return $this->getExceptionHandler($exception);
        }
    }

    /**
     * @return Client
     */
    public function client()
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());

        $stack->push(Middleware::mapResponse(function (ResponseInterface $response) {
            $limit = $response->getHeader('X-RateLimit-Limit');
            $this->rateLimit = is_array($limit) ? $limit[0] : $limit;

            $remainder = $response->getHeader('X-RateLimit-Remaining');
            $this->rateLimitRemainder = is_array($remainder) ? $remainder[0] : $remainder;

            return $response;
        }));

        $this->client = new Client([
            'handler' => $stack,
            'base_uri' => $this->route('api'),
            'http_errors' => false,
            'verify' => false,
            'headers' => array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ], [
                'Authorization' => sprintf('Bearer %s', $this->accessToken()),
            ]),
        ]);

        return $this->client;
    }

    public function store()
    {
        return $this->store;
    }

    /**
     * @param array $scopes
     * @return Auth
     * @throws \Exception
     */
    public function setAuthorizationScopes(array $scopes = [])
    {
        $external = $this->getExternalAuthorizationScopes();

        $allowed = array_filter(array_map(function($scope) use ($scopes, $external) {
            return !in_array($scope, array_diff($scopes, $external)) ? $scope : null;
        }, array_unique(array_merge($this->scopes(), $scopes))));

        $this->store()->set($this->persistMappings['scopes'], $allowed);

        return $this;
    }

    /**
     * @return array|mixed
     * @todo add error handling before processing the response
     *
     */
    public function getExternalAuthorizationScopes()
    {
        $response = $this->client()->get($this->route('scopes'));

        return json_decode((string)$response->getBody()->getContents(), true);
    }

    /**
     * @return string
     */
    private function getTransferScope()
    {
        return count($this->scopes())
            ? implode(' ', $this->scopes())
            : '';
    }

    /**
     * Login using the credentials provided through configuration.
     *
     * @param array $scopes
     * @param string $returnTo
     *
     * @return void
     * @throws \Exception
     */
    public function login(array $scopes = [], string $returnTo = null)
    {
        if (!$this->isLoggedIn()) {
            return $this->authorize($scopes, $returnTo);
        }
    }

    /**
     * @param array $scopes
     * @param string|null $returnTo
     * @throws \Exception
     */
    private function authorize(array $scopes = [], string $returnTo = null)
    {
        $this->setAuthorizationScopes($scopes);

        $this->setState([
            'return_to' => is_null($returnTo)
                ? $this->getCurrentUrl()
                : $returnTo,
        ]);

        return $this->redirect($this->route('authorize'), [
            'client_id' => $this->config('client_id'),
            'redirect_uri' => $this->config('redirect_uri'),
            'response_type' => $this->config('response_type'),
            'scope' => $this->getTransferScope(),
            'state' => $this->getTransferState(),
        ]);
    }

    /**
     * Get the URL based on current $_SERVER settings.
     *
     * @todo add flexibility to schema
     *
     * @param boolean $includePath
     * @return string
     */
    private function getCurrentUrl($includePath = true)
    {
        $schema = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
        $hostname = $_SERVER['HTTP_HOST'];
        $requestUri = ltrim($_SERVER['REQUEST_URI'], '/');

        return sprintf("%s://%s/%s", $schema, $hostname, $includePath ? $requestUri : '');
    }

    /**
     * Handle any callback action.
     */
    public function callback()
    {
        if (isset($_GET['action']) && !empty($_GET['action'])) {
            switch ($_GET['action']) {
                case 'logout':
                    return $this->logout();
                case 'token':
                    return $this->getAuthorizationToken();
            }
        } else if (!empty($this->getAuthorizationCode())) {
            return $this->getAuthorizationToken();
        }

        // @todo other actions should be dismissed on callback route / throw exception / redirect latest state?
    }

    /**
     * Get authorization token
     */
    private function getAuthorizationToken()
    {
        $response = $this->client()->post($this->route('token'), [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->config('client_id'),
                'client_secret' => $this->config('client_secret'),
                'redirect_uri' => $this->config('redirect_uri'),
                'code' => $this->getAuthorizationCode(),
                'state' => $this->getTransferState(),
            ],
        ]);

        if (in_array($response->getStatusCode(), [200])) {
            $data = json_decode((string)$response->getBody()->getContents(), true);

            $this->store()->set($this->persistMappings['access_token'], $data['access_token']);
            $this->store()->set($this->persistMappings['refresh_token'], $data['refresh_token']);
            $this->store()->set($this->persistMappings['token_type'], $data['token_type']);
            $this->store()->set($this->persistMappings['expires_at'], time() + $data['expires_in']);
        }

        if ($this->getState('return_to')) {
            return $this->redirect($this->getState('return_to'));
        }
    }

    /**
     * @return mixed
     */
    public function scopes()
    {
        if (empty($this->store()->get($this->persistMappings['scopes']))) {
            return [];
        }

        return $this->store()->get($this->persistMappings['scopes']);
    }

    /**
     * Store the access token in order to communicate with the API in authorized matter.
     *
     * @return mixed
     */
    public function accessToken()
    {
        return $this->store()->get($this->persistMappings['access_token']);
    }

    /**
     * Store the refresh token in order to keep the session alive when possible.
     *
     * @return mixed
     */
    public function refreshToken()
    {
        return $this->store()->get($this->persistMappings['refresh_token']);
    }

    /**
     * Verify if the user is authorized.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if (!empty($this->accessToken())) {
            return $this->store()->get($this->persistMappings['expires_at']) >= time();
        }

        return false;
    }

    /**
     * Get the current user model otherwise authorize the request.
     *
     * @todo add `config->persist_user` check
     *
     * @return UserModel|void
     * @throws \Exception
     */
    public function user()
    {
        if ($this->isLoggedIn()) {
            if (empty($this->store()->get($this->persistMappings['user_info']))) {
                $response = $this->client()->get($this->route('user_info'));

                if ($response->getStatusCode() === 200) {
                    $json = json_decode((string)$response->getBody()->getContents(), true);

                    $this->store()->set(
                        $this->persistMappings['user_info'],
                        array_key_exists('data', $json) ? $json['data'] : $json
                    );
                } else if ($response->getStatusCode() === 401) {
                    return $this->refresh()->user();
                }
            }

            return (new UserModel($this->store()->get($this->persistMappings['user_info'])));
        }

        return $this->login();
    }

    public function refresh()
    {
        if ($this->isLoggedIn() && !empty($this->refreshToken())) {
            $response = $this->client()->post($this->route('refresh'), [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->refreshToken(),
                    'client_id' => $this->config('client_id'),
                    'client_secret' => $this->config('client_secret'),
                    'scope' => $this->getTransferScope(),
                ],
            ]);

            if (in_array($response->getStatusCode(), [200, 201, 204])) {
                $data = json_decode((string)$response->getBody()->getContents(), true);

                if (array_key_exists('access_token', $data)) {
                    $this->store()->set($this->persistMappings['access_token'], $data['access_token']);
                }

                if (array_key_exists('refresh_token', $data)) {
                    $this->store()->set($this->persistMappings['refresh_token'], $data['refresh_token']);
                }

                if (array_key_exists('token_type', $data)) {
                    $this->store()->set($this->persistMappings['token_type'], $data['token_type']);
                }

                if (array_key_exists('expires_in', $data)) {
                    $this->store()->set($this->persistMappings['expires_at'], time() + $data['expires_in']);
                }
            } else {
                return $this->logout();
            }
        } else {
            return $this->login();
        }

        return $this;
    }

    /**
     * @todo move logout process onto single sign on at all to separate current application logout or logout on all applications
     * @todo add correct redirect url if no `return_to` state was found
     */
    public function logout()
    {
        $this->client()->post($this->route('logout'));

        foreach ($this->persistMappings as $persistMapping) {
            $this->store()->delete($persistMapping);
        }

        return $this->redirect($this->getCurrentUrl(false));
    }
}
