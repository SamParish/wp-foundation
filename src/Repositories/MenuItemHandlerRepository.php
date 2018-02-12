<?php


namespace JB000\WordPress\Repositories;



use Illuminate\Contracts\Container\Container;
use JB000\WordPress\Contracts\IMenuItemHandler;

class MenuItemHandlerRepository extends BaseRepository
{

    public function __construct(Container $container, array $handlers)
    {
        parent::__construct($container, IMenuItemHandler::class, $handlers);
    }


    public function runHandlers($menuItem)
    {
	    //get the handler which will handle the menu item type
	    if($instance = $this->makeHandler($menuItem->type))
	    {
		    //run the instance
		    $instance->handle($menuItem);
	    }
    }
}