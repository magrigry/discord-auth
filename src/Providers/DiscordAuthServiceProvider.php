<?php

namespace Azuriom\Plugin\DiscordAuth\Providers;

use Azuriom\Extensions\Plugin\BasePluginServiceProvider;
use Azuriom\Models\Permission;
use Azuriom\Plugin\DiscordAuth\Models\Discord;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use MartinBean\Laravel\Socialite\DiscordProvider;

class DiscordAuthServiceProvider extends BasePluginServiceProvider
{
    /**
     * The plugin's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        // \Azuriom\Plugin\DiscordAuth\Middleware\ExampleMiddleware::class,
    ];

    /**
     * The plugin's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * The plugin's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // 'example' => \Azuriom\Plugin\DiscordAuth\Middleware\ExampleRouteMiddleware::class,
    ];

    /**
     * The policy mappings for this plugin.
     *
     * @var array
     */
    protected $policies = [
        // User::class => UserPolicy::class,
    ];

    /**
     * Register any plugin services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMiddlewares();
        require_once __DIR__.'/../../vendor/autoload.php';
    }

    /**
     * Bootstrap any plugin services.
     *
     * @return void
     */
    public function boot()
    {

        Blade::if('hasDiscordLinked', $this->bladeHasDiscordLinked());
        Blade::if('hasNotDiscordLinked', $this->bladeHasNotDiscordLinked());

        Socialite::extend('discord', function (Application $app) {
            $config = $app->make('config')->get('services.discord');

            $redirect = value(Arr::get($config, 'redirect'));

            return new DiscordProvider (
                $app->make('request'),
                $config['client_id'],
                $config['client_secret'],
                Str::startsWith($redirect, '/') ? $app->make('url')->to($redirect) : $redirect,
                Arr::get($config, 'guzzle', [])
            );
        });

        $this->loadViews();

        $this->loadTranslations();

        $this->loadMigrations();

        $this->registerRouteDescriptions();

        $this->registerAdminNavigation();

        $this->registerUserNavigation();

        Permission::registerPermissions([
            'discord-auth.admin' => 'discord-auth::admin.permission',
        ]);
    }

    /**
     * Returns the routes that should be able to be added to the navbar.
     *
     * @return array
     */
    protected function routeDescriptions()
    {
        return [
            'discord-auth.index' => 'discord-auth::messages.plugin_name',
        ];
    }

    /**
     * Return the admin navigations routes to register in the dashboard.
     *
     * @return array
     */
    protected function adminNavigation()
    {
        return [
            'discord-auth' => [
                'name' => 'discord-auth::admin.nav.title',
                'icon' => 'fas fa-hammer',
                'route' => 'discord-auth.admin.settings',
                'permission' => 'discord-auth.admin'
            ],
        ];
    }

    /**
     * Return the user navigations routes to register in the user menu.
     *
     * @return array
     */
    protected function userNavigation()
    {
        return [
            //
        ];
    }

    private function bladeHasDiscordLinked()
    {
        return function () {

            if (Auth::guest()) {
                return false;
            }

            return Discord::where('user_id', Auth::user()->id)->exists();
        };
    }

    private function bladeHasNotDiscordLinked()
    {
        return function () {

            if (Auth::guest()) {
                return true;
            }

            return !Discord::where('user_id', Auth::user()->id)->exists();
        };
    }
}
