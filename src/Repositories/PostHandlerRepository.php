<?php


namespace SamParish\WordPress\Repositories;


use Illuminate\Contracts\Container\Container;
use SamParish\WordPress\Contracts\IPostHandler;
use SamParish\WordPress\Models\Post;

class PostHandlerRepository extends BaseRepository
{

    /**
     * PostHandlerRepository constructor.
     *
     * @param Container $container
     * @param array     $handlers
     */
    public function __construct(Container $container, array $handlers)
    {

        parent::__construct($container, IPostHandler::class, $handlers);
    }


    /**
     * Processes the post model through each handler registered with the repository.
     *
     * @param Post $post
     */
    public function runHandlers(Post $post)
    {

        if(count($this->handlers)>0)
        {
            foreach ($this->handlers as $key=>$name)
            {
                $this->makeHandler($key)->handle($post);

                //add handler to the entity
                if(property_exists($post,'handlers') && is_array($post->handlers))
                    $post->handlers[] = $name;
            }
        }
    }
}