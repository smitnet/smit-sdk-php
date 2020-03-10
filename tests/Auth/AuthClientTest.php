<?php

namespace SMIT\SDK\Tests\Auth;

use SMIT\SDK\Tests\TestCase;
use SMIT\SDK\Auth\Stores\SessionStore;
use SMIT\SDK\Auth\Auth;
use ReflectionClass;
use InvalidArgumentException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;

class AuthClientTest extends TestCase
{
    public static $config = [
        'response_mode' => 'query',
        'response_type' => 'code',
        'version' => '1.0',
    ];

    public static $routeMappings = [];

    public static $store = null;

    public static $headers = [
        'content-type' => 'json',
    ];

    public function setUp()
    {
        parent::setUp();

        self::$config = array_merge(self::$config, [
            'domain'        => '__domain__',
            'client_id'     => '__client_id__',
            'client_secret' => '__client_secret__',
            'redirect_uri'  => '__redirect_uri__',
        ]);

        self::$routeMappings = [
            'api' => sprintf('https://%s/api/%s', self::$config['domain'], self::$config['version']),
            'authorize' => sprintf('https://%s/authorize', self::$config['domain']),
            'logout' => sprintf('https://%s/logout', self::$config['domain']),
            'token' => sprintf('https://%s/token', self::$config['domain']),
            'verify' => sprintf('https://%s/api/%s/verify', self::$config['domain'], self::$config['version']),
            'user_info' => sprintf('https://%s/api/%s/me', self::$config['domain'], self::$config['version']),
            'refresh' => sprintf('https://%s/token', self::$config['domain']),
            'scopes' => sprintf('https://%s/api/%s/scopes', self::$config['domain'], self::$config['version']),
        ];

        self::$store = new SessionStore();
    }

    public function tearDown()
    {
        parent::tearDown();

        $_GET = [];
        $_COOKIE = [];
        $_SESSION = [];
    }

    public function testThatRequiredConfigurationAreCorrectlySet()
    {
        $client = new Auth(self::$config, self::$store);

        $this->assertEquals(self::$config['domain'], $client->config('domain'));
        $this->assertEquals(self::$config['client_id'], $client->config('client_id'));
        $this->assertEquals(self::$config['client_secret'], $client->config('client_secret'));
        $this->assertEquals(self::$config['redirect_uri'], $client->config('redirect_uri'));
    }

    public function testThatRequiredConfigurationIsMissing()
    {
        $config = self::$config;

        unset($config['domain']);

        $this->expectException(InvalidArgumentException::class);

        $client = new Auth($config, self::$store);

        $this->assertArrayNotHasKey('domain', $client->config());
    }

    public function testRouteMappingsAreSetCorrectlyAfterConfiguration()
    {
        $client = new Auth(self::$config, self::$store);

        $routes = $this->getPropertyValue($client, 'routeMappings');

        $this->assertSame(array_diff(self::$routeMappings, $routes), array_diff($routes, self::$routeMappings));
    }

    public function testThatGetAuthorizeUrlHasCorrectCredentials()
    {
        $client = new Auth(self::$config, self::$store);

        $redirect = $client->getAuthorizeUrl();

        $segments = parse_url($redirect);
        $this->assertArrayHasKey('query', $segments);


        $query = explode('&', $segments['query']);
        $this->dd($query);

        $state = base64_encode(json_encode([]));
        $nonce = '';

        $this->dd(base64_decode($query['state']));
        $this->assertContains(sprintf('client_id=%s', self::$config['client_id']), $query);
        $this->assertContains(sprintf('response_mode=%s', self::$config['response_mode']), $query);
        $this->assertContains(sprintf('response_type=%s', self::$config['response_type']), $query);
        $this->assertContains(sprintf('state=%s', self::$config['response_type']), $query);
        $this->assertContains(sprintf('nonce=%s', self::$config['response_type']), $query);


        // $mock = new MockHandler([
        //     new Response(301, [], null),
        // ]);

        // $handlerStack = HandlerStack::create($mock);

        // $client = new Client(['handler' => $handlerStack]);

        // $response = $client->get(self::$routeMappings['authorize']);

        // $this->assertEquals(301, $response->getStatusCode());
    }

    // @todo test that we do not get redirected when fail from incorrect credentials

    // @todo test that we receive authorization_code from authorize url redirect

    // @todo test that we can request access_token from token url

    // @todo test that we can decode access_token

    // @todo test that we have required claims `aud` and `sub` within access_token

    // @todo test that we can fetch user information from authorization server

    // @todo test that we can use a custom store driver to store information (session)

    // @todo test that we can verify access_token with client_secret

    // @todo test that we receive exceptions on sdk from authorization server
}
