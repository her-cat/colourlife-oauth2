<?php

namespace HerCat\ColourlifeOAuth2;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Overtrue\Socialite\SocialiteManager;

/**
 * Class ServiceProvider.
 */
class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the provider.
     */
    public function register()
    {
        if (!$this->app->has(SocialiteManager::class)) {
            $this->app->singleton(SocialiteManager::class, function ($app) {
                $config = array_merge(config('socialite', []), config('services', []));
                return new SocialiteManager($config, $app->make('request'));
            });
        }

        $this->app->singleton(ColourlifeOAuth2Provider::class, function ($app) {
            $socialite = $app->get(SocialiteManager::class);

            $socialite->extend('colourlife', function ($config) use ($socialite) {
                $config = $config['colourlife'] ?? [];

                $provider = $socialite->buildProvider(ColourlifeOAuth2Provider::class, $config);

                return $provider->environment($config['environment'] ?? 'prod');
            });

            return $socialite->driver('colourlife');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [ColourlifeOAuth2Provider::class];
    }
}
