<?php

namespace Vanderb\LaravelBlinky;

use Illuminate\Support\ServiceProvider;

class BlinkyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerExtension();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

        $app = $this->app;
        $resolver = $app['view.engine.resolver'];
        
        $app->singleton('blinky.compiler', function ($app) {
            $cache = $app['config']['view.compiled'];
            
            return new BlinkyCompiler($app['blade.compiler'], $app['files'], $cache);
        });

        $resolver->register('inky', function () use ($app) {
            return new BlinkyCompilerEngine($app['blinky.compiler'], $app['files']);
        });
    }
    
    protected function registerExtension()
    {
        $this->app['view']->addExtension('inky.php', 'inky');
    }

}
