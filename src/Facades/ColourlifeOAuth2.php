<?php

/*
 * This file is part of the her-cat/colourlife-oauth2.
 *
 * (c) her-cat <i@her-cat.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace HerCat\ColourlifeOAuth2\Facades;

use HerCat\ColourlifeOAuth2\ColourlifeOAuth2Provider as ColourlifeOAuth2Provider;
use Overtrue\Socialite\AccessTokenInterface;
use Overtrue\Socialite\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Illuminate\Support\Facades\Facade;

/**
 * Class ColourlifeOAuth2.
 *
 * @method static string getRedirectUrl()
 * @method static RedirectResponse redirect()
 * @method static User user(AccessTokenInterface $token = null)
 * @method static ColourlifeOAuth2Provider scopes(array $scopes)
 * @method static ColourlifeOAuth2Provider with(array $parameters)
 * @method static ColourlifeOAuth2Provider setRedirectUrl($redirectUrl)
 * @method static ColourlifeOAuth2Provider withRedirectUrl($redirectUrl)
 * @method static ColourlifeOAuth2Provider setAccessToken(AccessTokenInterface $accessToken)
 */
class ColourlifeOAuth2 extends Facade
{
    /**
     * Return the facade accessor.
     *
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'HerCat\\ColourlifeOAuth2\\ColourlifeOAuth2Provider';
    }
}
