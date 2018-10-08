<?php 
namespace Megaads\Generatepagefeeds;

use Illuminate\Support\ServiceProvider;

class GeneratepagefeedsServiceProvider extends ServiceProvider
{
    public function boot()
    {

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