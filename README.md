<h1 align="center"> Colourlife OAuth2 </h1>

<p align="center"> 基于 <a href="https://github.com/overtrue/socialite">overtrue/socialite</a> 的彩之云 OAuth 2.0 授权 SDK</p>

[![Build Status](https://travis-ci.org/her-cat/colourlife-oauth2.svg?branch=master)](https://travis-ci.org/her-cat/colourlife-oauth2)
[![StyleCI build status](https://github.styleci.io/repos/214346677/shield)](https://github.styleci.io/repos/214346677)

## 安装

```shell
$ composer require her-cat/colourlife-oauth2 -vvv
```

## 单元测试

```shell
$ composer test
```

## 使用

### 基本使用

`authorize.php`:

```php
<?php

require_once './vendor/autoload.php';

use HerCat\ColourlifeOauth2\ColourlifeOAuth2Provider;
use Overtrue\Socialite\SocialiteManager;

$config = [
    'colourlife' => [
        'client_id' => 'your-ice-application-id',
        'client_secret' => 'your-ice-secret',
        'redirect' => 'http://localhost/callback.php',
        'environment' => 'prod',
    ],
];

$socialite = new SocialiteManager($config);

$socialite->extend('colourlife', function ($config) use ($socialite) {
    $config = $config['colourlife'] ?? [];

    /** @var ColourlifeOAuth2Provider $provider */
    $provider = $socialite->buildProvider(ColourlifeOAuth2Provider::class, $config);

    return $provider->environment($config['environment'] ?? 'prod');
});

$response = $socialite->driver('colourlife')->redirect();

$response->send();
```

`callback.php`:

```php
<?php

require_once './vendor/autoload.php';

use HerCat\ColourlifeOauth2\ColourlifeOAuth2Provider;
use Overtrue\Socialite\SocialiteManager;

$config = [
    'colourlife' => [
        'client_id' => 'your-ice-application-id',
        'client_secret' => 'your-ice-secret',
        'redirect' => 'http://localhost/callback.php',
        'environment' => 'prod',
    ],
];

$socialite = new SocialiteManager($config);

$socialite->extend('colourlife', function ($config) use ($socialite) {
    $config = $config['colourlife'] ?? [];

    /** @var ColourlifeOAuth2Provider $provider */
    $provider = $socialite->buildProvider(ColourlifeOAuth2Provider::class, $config);

    return $provider->environment($config['environment'] ?? 'prod');
});

$user = $socialite->driver('colourlife')->user();

$user->getId();                 // xxxxxxxxxx
$user->getName();               // her-cat
$user->getProviderName();       // colourlife
$user->getAttribute('mobile');  // 18500000001
```

### 在 Laravel 中使用

#### 配置

1. 安装完成后，在 `config/app.php` 中注册 `HerCat\ColourlifeOAuth2\ServiceProvider`:

```php
'providers' => [
    // Other service providers...
    HerCat\ColourlifeOAuth2\ServiceProvider::class,
],
```

2. 将下面这一行添加到 `config/app.php` 的 `aliases` 部分:

```php
'aliases' => [
    // Other aliases...
    'ColourlifeOAuth2' => HerCat\ColourlifeOAuth2\Facades\ColourlifeOAuth2::class,
],
```

3. 在 `config/socialite.php` 或 `config/services.php` 文件中配置 `OAuth` 服务凭证:

```php
<?php

return [
    
    //...
    
    'colourlife' => [
        'client_id' => 'your-ice-application-id',
        'client_secret' => 'your-ice-secret',
        'redirect' => config('app.url').'/oauth/colourlife/callback',
        'environment' => 'prod', // OAuth 服务环境，dev: 测试，prod：正式
    ],
    
    //...
    
];
```

#### 使用

```php
<?php

namespace App\Http\Controllers;

use HerCat\ColourlifeOAuth2\Facades\ColourlifeOAuth2;

class AuthController extends Controller
{
    public function redirect()
    {
        return ColourlifeOAuth2::redirect();
    }

    public function handleCallback()
    {
        $user = ColourlifeOAuth2::user();

        print_r($user->getName());
    }
}
```

添加路由:

```php
Route::get('/oauth/colourlife', 'AuthController@redirect');
Route::get('/oauth/colourlife/callback', 'AuthController@handleCallback');
```

有关更多用法，请参阅 [overtrue/socialite](https://github.com/overtrue/socialite)。

## 参考

- [overtrue/socialite](https://github.com/overtrue/socialite)
- [overtrue/laravel-socialite](https://github.com/overtrue/laravel-socialite)

## License

MIT