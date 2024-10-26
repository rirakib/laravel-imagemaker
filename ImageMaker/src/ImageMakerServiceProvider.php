<?php

namespace ImageMaker;
use Illuminate\Support\ServiceProvider;


class ImageMakerServiceProvider extends ServiceProvider{


        /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(ImageMaker::class, function ($app) {
            return new ImageMaker($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

    }

}
