<?php

namespace ImageMaker;

use Illuminate\Support\ServiceProvider;
use ImageMaker\ImageMaker;

class ImageMakerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(ImageMaker::class, function ($app) {
            return new ImageMaker($app);
        });
    }

    public function register()
    {

    }
}
