<?php 
namespace Megaads\Generatepagefeeds;

use Illuminate\Support\ServiceProvider;

class GeneratepagefeedServiceProvider extends ServiceProvider
{
    public function boot() 
    {
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/routes.php';
        }
        $this->publishConfig();
    }

    public function register() 
    {
        
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