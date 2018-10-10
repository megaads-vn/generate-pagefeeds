<?php
namespace Megaads\Generatepagefeeds;

use Illuminate\Support\ServiceProvider;
use Megaads\Generatepagefeeds\Services\GoogleClientService;

class GeneratepagefeedsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/routes.php';
        }
        $this->publishConfig();
        include __DIR__.'/../vendor/autoload.php';
    }

    public function register()
    {
        $this->app->singleton("googleClient", function() {
            return new GoogleClientService();
        });
    }

    private function publishConfig()
    {
        $path = $this->getConfigPath();
        $this->publishes([$path => config_path('pagefeeds.php')], 'config');
    }

    private function getConfigPath()
    {
        return __DIR__.'/../config/pagefeeds.php';
    }
}