<?php

namespace HerCat\ColourlifeOAuth2;

use HerCat\ColourlifeOAuth2\Exceptions\InvalidArgumentException;
use Overtrue\Socialite\AccessToken;
use Overtrue\Socialite\AccessTokenInterface;
use Overtrue\Socialite\AuthorizeFailedException;
use Overtrue\Socialite\ProviderInterface;
use Overtrue\Socialite\Providers\AbstractProvider;
use Overtrue\Socialite\User;

class ColourlifeOAuth2Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Provider name.
     *
     * @var string
     */
    protected $name = 'colourlife';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['snsapi_base'];

    /**
     * The Current application environment.
     *
     * @var string
     */
    protected $environment = 'prod';

    /**
     * The base url of Colourlife API.
     *
     * @var string
     */
    protected $baseUrls = [
        'dev' => 'https://oauth2-czytest.colourlife.com',
        'prod' => 'https://oauth2czy.colourlife.com',
    ];

    /**
     * Setting up the current environment.
     *
     * @param string $environment
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function environment($environment)
    {
        if (!isset($this->baseUrls[$environment])) {
            throw new InvalidArgumentException('The environment must be dev or prod.');
        }

        $this->environment = $environment;

        return $this;
    }

    /**
     * Get the base URL.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrls[$this->environment];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(sprintf('%s/oauth2/authorize', $this->getBaseUrl()), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return sprintf('%s/oauth/access_token', $this->getBaseUrl());
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken($code)
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'query' => $this->getTokenFields($code),
        ]);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken(AccessTokenInterface $token)
    {
        $url = sprintf('%s/oauth/user/info?access_token=%s', $this->getBaseUrl(), $token->getToken());

        $response = $this->getHttpClient()->get($url);

        $user = json_decode($response->getBody(), true);

        if (!isset($user['code']) || 0 != $user['code']) {
            return [];
        }

        return $user['content'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return new User([
            'id' => $this->arrayItem($user, 'openid'),
            'username' => $this->arrayItem($user, 'nickname'),
            'nickname' => $this->arrayItem($user, 'nickname'),
            'name' => $this->arrayItem($user, 'nickname'),
            'avatar' => $this->arrayItem($user, 'head_img_url'),
            'mobile' => $this->arrayItem($user, 'mobile'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = array_merge([
            'application_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'response_type' => 'code',
        ], $this->parameters);

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'application_id' => $this->clientId,
            'secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        if (!is_array($body)) {
            $body = json_decode($body, true);
        }

        if (empty($body['content']['access_token'])) {
            throw new AuthorizeFailedException('Authorize Failed: '.json_encode($body, JSON_UNESCAPED_UNICODE), $body);
        }

        return new AccessToken($body['content']);
    }
}
