<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use HerCat\ColourlifeOAuth2\ColourlifeOAuth2Provider;
use HerCat\ColourlifeOAuth2\Exceptions\InvalidArgumentException;
use Mockery\MockInterface;
use Overtrue\Socialite\AccessToken;
use Overtrue\Socialite\AuthorizeFailedException;
use Overtrue\Socialite\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ColourlifeOAuth2ProviderTest extends TestCase
{
    /**
     * @param string $redirectUri
     * @return MockInterface|\Mockery\LegacyMockInterface
     */
    public function getProvider($redirectUri = 'mock-redirect-uri')
    {
        return \Mockery::mock(ColourlifeOAuth2Provider::class, [
            Request::create('foo'),
            'mock-client-id',
            'mock-client-secret',
            $redirectUri
        ])->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testEnvironmentAndGetBaseUrl()
    {
        $provider = $this->getProvider();

        $this->assertSame($provider, $provider->environment('dev'));

        $baseUrl = $provider->environment('dev')->getBaseUrl();
        $this->assertSame('https://oauth2-czytest.colourlife.com', $baseUrl);

        $baseUrl = $provider->environment('prod')->getBaseUrl();
        $this->assertSame('https://oauth2czy.colourlife.com', $baseUrl);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The environment must be dev or prod.');

        $provider->environment('error-environment');
    }

    public function testGetAuthUrl()
    {
        $provider = $this->getProvider();

        $authUrl = '%s/oauth2/authorize?application_id=mock-client-id&redirect_uri=mock-redirect-uri&scope=snsapi_base&response_type=code&state=foo';

        $this->assertSame(sprintf($authUrl, 'https://oauth2czy.colourlife.com'), $provider->getAuthUrl('foo'));
        $this->assertSame(sprintf($authUrl, 'https://oauth2-czytest.colourlife.com'), $provider->environment('dev')->getAuthUrl('foo'));
    }

    public function testGetTokenUrl()
    {
        $provider = $this->getProvider();

        $tokenUrl = '%s/oauth/access_token';

        $this->assertSame(sprintf($tokenUrl, 'https://oauth2czy.colourlife.com'), $provider->getTokenUrl());
        $this->assertSame(sprintf($tokenUrl, 'https://oauth2-czytest.colourlife.com'), $provider->environment('dev')->getTokenUrl());
    }

    public function testGetAccessToken()
    {
        $provider = $this->getProvider();

        $httpClient = Mockery::mock(Client::class);

        $response = new Response(200, [], '{"content": {"access_token": "mock-access-token"}}');

        $httpClient->shouldReceive('get')->with(
            'https://oauth2czy.colourlife.com/oauth/access_token',
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => [
                    'application_id' => 'mock-client-id',
                    'secret' => 'mock-client-secret',
                    'code' => 'mock-code',
                    'grant_type' => 'authorization_code',
                ],
            ]
        )->andReturn($response);

        $provider->shouldReceive('getHttpClient')->andReturn($httpClient);

        /** @var AccessToken $accessToken */
        $accessToken = $provider->getAccessToken('mock-code');

        $this->assertInstanceOf(AccessToken::class, $accessToken);
        $this->assertSame('mock-access-token', $accessToken->getToken());
    }

    public function testGetAccessTokenWithAuthorizeFailed()
    {
        $provider = $this->getProvider();

        $httpClient = Mockery::mock(Client::class);

        $response = new Response(200, [], '{"content": "error"}');

        $httpClient->shouldReceive('get')->with(
            'https://oauth2czy.colourlife.com/oauth/access_token',
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => [
                    'application_id' => 'mock-client-id',
                    'secret' => 'mock-client-secret',
                    'code' => 'mock-code',
                    'grant_type' => 'authorization_code',
                ],
            ]
        )->andReturn($response);

        $provider->shouldReceive('getHttpClient')->andReturn($httpClient);

        $this->expectException(AuthorizeFailedException::class);
        $this->expectExceptionMessage('Authorize Failed: {"content":"error"}');

        $provider->getAccessToken('mock-code');
    }

    public function testGetUserByToken()
    {
        $provider = $this->getProvider();

        $httpClient = Mockery::mock(Client::class);

        $accessToken = new AccessToken(['access_token' => 'mock-access-token']);

        $success = new Response(200, [], '{"code": 0, "content": "mock-content"}');
        $error = new Response(200, [], '{"code": -1, "content": "mock-content"}');

        $httpClient->shouldReceive('get')
            ->with('https://oauth2czy.colourlife.com/oauth/user/info?access_token=mock-access-token')
            ->andReturn($error, $success);

        $provider->shouldReceive('getHttpClient')->andReturn($httpClient);

        $this->assertEmpty($provider->getUserByToken($accessToken));
        $this->assertSame('mock-content', $provider->getUserByToken($accessToken));
    }

    public function testMapUserToObject()
    {
        $provider = $this->getProvider();

        $array = [
            'openid' => 'mock-openid',
            'nickname' => 'mock-nickname',
            'head_img_url' => 'mock-head-img-url',
            'mobile' => 'mock-mobile',
        ];

        /** @var User $user */
        $user = $provider->mapUserToObject($array);

        $this->assertSame('mock-openid', $user->getId());
        $this->assertSame('mock-nickname', $user->getUsername());
        $this->assertSame('mock-nickname', $user->getNickname());
        $this->assertSame('mock-nickname', $user->getName());
        $this->assertSame('mock-head-img-url', $user->getAvatar());
        $this->assertSame('mock-mobile', $user->getAttribute('mobile'));

        unset($user['id']);

        $this->assertNull($user->getId());
    }

    public function testGetCodeFields()
    {
        $provider = $this->getProvider();

        $fields = [
            'application_id' => 'mock-client-id',
            'redirect_uri' => 'mock-redirect-uri',
            'scope' => 'snsapi_base',
            'response_type' => 'code',
            'state' => 'mock-state',
        ];

        $this->assertSame($fields, $provider->getCodeFields('mock-state'));

        $fields['scope'] = 'mock-scope-1,mock-scope-2';
        $this->assertSame($fields, $provider->scopes(['mock-scope-1', 'mock-scope-2'])->getCodeFields('mock-state'));
    }

    public function testGetTokenFields()
    {
        $provider = $this->getProvider();

        $this->assertSame([
            'application_id' => 'mock-client-id',
            'secret' => 'mock-client-secret',
            'code' => 'mock-code',
            'grant_type' => 'authorization_code',
        ], $provider->getTokenFields('mock-code'));
    }

    public function testParseAccessToken()
    {
        $provider = $this->getProvider();

        /** @var AccessToken $accessToken */
        $accessToken = $provider->parseAccessToken('{"content": {"access_token": "mock-access-token"}}');

        $this->assertInstanceOf(AccessToken::class, $accessToken);
        $this->assertSame('mock-access-token', $accessToken->getToken());

        $accessToken = $provider->parseAccessToken(['content' => ['access_token' => 'mock-access-token']]);

        $this->assertInstanceOf(AccessToken::class, $accessToken);
        $this->assertSame('mock-access-token', $accessToken->getToken());

        $this->expectException(AuthorizeFailedException::class);
        $this->expectExceptionMessage('Authorize Failed: {"content":"error"}');

        $provider->parseAccessToken(['content' => 'error']);
    }

    public function testRedirectGeneratesTheProperSymfonyRedirectResponse()
    {
        $provider = $this->getProvider();

        $provider->shouldReceive('getAuthUrl')->andReturn('https://auth.url');

        /** @var RedirectResponse $response */
        $response = $provider->redirect();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://auth.url', $response->getTargetUrl());
    }

    public function testRedirectUrl()
    {
        $this->assertNull($this->getProvider(null)->getRedirectUrl());

        $provider = $this->getProvider('redirect_uri');
        $this->assertSame('redirect_uri', $provider->getRedirectUrl());

        $provider->setRedirectUrl('mock-redirect');
        $this->assertSame('mock-redirect', $provider->getRedirectUrl());

        $provider->withRedirectUrl('mock-redirect-uri');
        $this->assertSame('mock-redirect-uri', $provider->getRedirectUrl());
    }

    public function testUserReturnsAUserInstanceForTheAuthenticatedRequest()
    {
        $request = Request::create('foo', 'GET', [
            'state' => str_repeat('A', 40), 'code' => 'mock-code'
        ]);

        $session = Mockery::mock(SessionInterface::class);
        $session->shouldReceive('get')
            ->once()
            ->with('state')
            ->andReturn(str_repeat('A', 40));

        $request->setSession($session);

        $provider = \Mockery::mock(ColourlifeOAuth2Provider::class, [
            $request,
            'mock-client-id',
            'mock-client-secret',
            'mock-redirect-uri'
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $data = [
            'code' => 0,
            'content' => [
                'openid' => 'mock-openid',
                'nickname' => 'mock-nickname',
                'head_img_url' => 'mock-head-img-url',
                'mobile' => 'mock-mobile',
            ],
        ];

        $httpClient = Mockery::mock(Client::class);
        $response = new Response(200, [], '{"content": {"access_token": "mock-access-token"}}');
        $userInfo = new Response(200, [], json_encode($data));

        $httpClient->shouldReceive('get')->with(
            'https://oauth2czy.colourlife.com/oauth/access_token',
            [
                'headers' => ['Accept' => 'application/json'],
                'query' => [
                    'application_id' => 'mock-client-id',
                    'secret' => 'mock-client-secret',
                    'code' => 'mock-code',
                    'grant_type' => 'authorization_code',
                ],
            ]
        )->andReturn($response);

        $httpClient->shouldReceive('get')->with(
            'https://oauth2czy.colourlife.com/oauth/user/info?access_token=mock-access-token'
        )->andReturn($userInfo);

        $provider->shouldReceive('getHttpClient')->andReturn($httpClient);

        /** @var User $user */
        $user = $provider->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('mock-openid', $user->getId());
        $this->assertSame('colourlife', $user->getProviderName());
    }
}
