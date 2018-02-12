<?php


namespace JB000\WordPress;

use Illuminate\Cache\Repository;
use JB000\WordPress\Factories\PostFactory;
use JB000\WordPress\Factories\TermFactory;
use JB000\WordPress\Repositories\MenuItemHandlerRepository;
use JB000\WordPress\Repositories\PostHandlerRepository;
use JB000\WordPress\Repositories\ShortCodeRepository;

class WordPressServiceProvider extends \Illuminate\Support\ServiceProvider
{



    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //bind the PostHandlerRepository
        $this->app->bind(PostHandlerRepository::class,function()
        {
            return new PostHandlerRepository($this->app,config('wordpress.post_handlers'));
        });

        //bind the ShortCodeRepository
        $this->app->bind(ShortCodeRepository::class, function()
        {
            return new ShortCodeRepository($this->app,config('wordpress.shortcode_handlers'));
        });

        //bind
        $this->app->bind(MenuItemHandlerRepository::class,function()
        {
           return new MenuItemHandlerRepository($this->app,config('wordpress.menu_item_handlers'));
        });


        //bind the PostFactory
        $this->app->bind(PostFactory::class,function()
        {
            return new PostFactory(
                $this->app->make(Repository::class),
                $this->app->make(PostHandlerRepository::class),
                config('wordpress'));
        });

        //bind the TermFactory
        $this->app->bind(TermFactory::class,function()
        {
           return new TermFactory(
               $this->app->make(Repository::class),
               $this->app->make(MenuItemHandlerRepository::class),
               config('wordpress'));
        });

    }


    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {


    }
}