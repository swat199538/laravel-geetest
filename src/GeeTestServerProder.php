<?php
/**
 * Created by PhpStorm.
 * User: wangl
 * Date: 2018/5/4
 * Time: 15:22
 */
namespace Swat\GeeTest;

class GeeTestServerProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__. '/config.php'=> config_path('geetest.php')
        ], 'geetest');
    }

    public function register()
    {
        $this->app->singleton('geetest', function (){
            return $this->app->make('Swat\GeeTest\GeeTestLib');
        });
    }

}